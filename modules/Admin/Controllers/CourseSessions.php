<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Catalog\Models\CourseModel;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Catalog\Models\InstructorModel;
use Modules\Catalog\Models\VenueModel;
use Modules\Commerce\Services\InventoryService;
use Modules\Core\Libraries\SecretBox;
use Modules\Learning\Models\AttendanceModel;
use Modules\Learning\Models\EnrolmentModel;
use Modules\Learning\Services\EnrolmentService;

/**
 * Dates and seats — the screen the school actually lives in.
 *
 * Deliberately not a BaseCrudController subclass, and the reason is one column
 * wide. The generic form renders every allowed field as an input, and
 * `course_sessions` carries `seats_sold` and `seats_reserved`, which are
 * inventory written only by InventoryService inside a transaction. A text box
 * over `seats_sold` is not a convenience: it is the mechanism by which a
 * thirteenth name arrives at a twelve-chair classroom, and no amount of care
 * afterwards buys back the corporate account that sent them. Those two numbers
 * are rendered here as text, never as fields, and `collect()` below builds its
 * payload from an explicit list rather than from the request, so a column added
 * to the table later cannot quietly become editable.
 *
 * Three further decisions this screen encodes:
 *
 *   - **`cancelled` is not in the status dropdown.** Cancelling a class is not
 *     a state change, it is a promise to eleven people who booked time off; it
 *     goes through `EnrolmentService::cancelSession()`, which needs a reason
 *     and cancels the enrolments with it. A dropdown option would let somebody
 *     close a class without anybody on it being told.
 *   - **Past dates are filtered, never deleted.** Last quarter's classes are
 *     the attendance register a certificate was issued on and the revenue a
 *     report is built from. They collapse behind a tab.
 *   - **Day rows are matched by date, not replaced.** `attendance` hangs off
 *     `session_days.id`; delete-and-reinsert on every save would orphan the
 *     register while looking like it worked.
 */
class CourseSessions extends BaseController
{
    /** Every status a session can be in — the index filter's vocabulary. */
    private const STATUSES = [
        'draft', 'open', 'confirmed', 'waitlist', 'full', 'running', 'completed', 'cancelled',
    ];

    /**
     * The statuses an administrator may choose.
     *
     * `confirmed` and `full` are missing because InventoryService::refreshStatus()
     * derives them from the seat count on every save — "this class is confirmed
     * to run" is a promise, and it should come from the number rather than from
     * whether somebody remembered to change a dropdown. `cancelled` is missing
     * for the reason in the class comment.
     */
    private const EDITABLE_STATUSES = ['draft', 'open', 'waitlist', 'running', 'completed'];

    /** What the register may say about somebody on a given day. */
    private const ATTENDANCE = ['present', 'absent', 'late', 'excused'];

    private const PER_PAGE = 40;

    /** Set when a joining link could not be encrypted, so the save can say so. */
    private ?string $linkError = null;

    // ── The list ────────────────────────────────────────────────────────────

    public function index()
    {
        helper(['norlanka', 'commerce']);
        $db    = db_connect();
        $today = date('Y-m-d');

        $filters = [
            'course' => (int) $this->request->getGet('course'),
            'mode'   => (string) $this->request->getGet('mode'),
            'status' => (string) $this->request->getGet('status'),
            'month'  => (string) $this->request->getGet('month'),
            'when'   => (string) $this->request->getGet('when'),
        ];
        // Everything from the query string is checked against a list before it
        // reaches a builder, so a hand-edited URL can only ever narrow the view.
        if (! in_array($filters['mode'], CourseSessionModel::MODES, true)) {
            $filters['mode'] = '';
        }
        if (! in_array($filters['status'], self::STATUSES, true)) {
            $filters['status'] = '';
        }
        if (preg_match('/^\d{4}-\d{2}$/', $filters['month']) !== 1) {
            $filters['month'] = '';
        }
        if (! in_array($filters['when'], ['upcoming', 'past', 'all'], true)) {
            $filters['when'] = 'upcoming';
        }

        // Rebuilt on every call rather than shared: a query builder is consumed
        // by the query it runs, so counting from one and then listing from the
        // same one counts the rows and lists none.
        $build = static function (?string $when = null) use ($filters, $today) {
            $q = (new CourseSessionModel())
                ->select('course_sessions.*, c.slug AS course_slug, c.title AS course_title, v.name AS venue_name, v.city AS venue_city, i.name AS instructor_name')
                ->join('courses c', 'c.id = course_sessions.course_id', 'left')
                ->join('venues v', 'v.id = course_sessions.venue_id', 'left')
                ->join('instructors i', 'i.id = course_sessions.instructor_id', 'left');

            if ($filters['course'] > 0) {
                $q->where('course_sessions.course_id', $filters['course']);
            }
            if ($filters['mode'] !== '') {
                $q->where('course_sessions.mode', $filters['mode']);
            }
            if ($filters['status'] !== '') {
                $q->where('course_sessions.status', $filters['status']);
            }
            if ($filters['month'] !== '') {
                // A range rather than a function on the column, so the index on
                // (status, start_date) is still usable and the SQL is the same
                // on SQLite and MySQL.
                $q->where('course_sessions.start_date >=', $filters['month'] . '-01')
                    ->where('course_sessions.start_date <=', date('Y-m-t', strtotime($filters['month'] . '-01')));
            }

            switch ($when ?? $filters['when']) {
                case 'past':
                    $q->where('course_sessions.start_date <', $today);
                    break;

                case 'all':
                    break;

                default:
                    // Self-paced study carries no date at all, so "not in the
                    // past" has to admit a null start_date or the whole
                    // on-demand library disappears from this screen.
                    $q->groupStart()
                        ->where('course_sessions.start_date IS NULL')
                        ->orWhere('course_sessions.start_date >=', $today)
                        ->groupEnd();
            }

            return $q;
        };

        $total = $build()->countAllResults();
        $page  = max(1, (int) $this->request->getGet('page'));
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min($page, $pages);

        $rows = $build()
            ->orderBy('course_sessions.start_date IS NULL', 'ASC', false)
            ->orderBy('course_sessions.start_date', $filters['when'] === 'past' ? 'DESC' : 'ASC')
            ->orderBy('course_sessions.id', 'ASC')
            ->findAll(self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        // Prices for the whole page in one query. Asking per row is forty
        // queries, and forty queries is how a listing page quietly becomes slow.
        $prices = [];
        $ids    = array_map('intval', array_column($rows, 'id'));
        if ($ids !== []) {
            foreach ($db->table('session_prices')->whereIn('session_id', $ids)->orderBy('currency', 'ASC')->get()->getResultArray() as $p) {
                $prices[(int) $p['session_id']][] = $p;
            }
        }

        // SUBSTR on a DATE reads as 'YYYY-MM' on both drivers this site runs
        // on, which is why the month picker is built here rather than from a
        // driver-specific date function.
        $months = $db->query(
            'SELECT SUBSTR(start_date, 1, 7) AS month, COUNT(*) AS n FROM course_sessions '
            . 'WHERE start_date IS NOT NULL GROUP BY SUBSTR(start_date, 1, 7) ORDER BY month ASC'
        )->getResultArray();

        return view('Modules\Admin\Views\sessions\index', [
            'title'    => 'Dates & seats',
            'active'   => 'course-sessions',
            'rows'     => $rows,
            'prices'   => $prices,
            'filters'  => $filters,
            'counts'   => [
                'upcoming' => $build('upcoming')->countAllResults(),
                'past'     => $build('past')->countAllResults(),
                'all'      => $build('all')->countAllResults(),
            ],
            'courses'  => $this->courseOptions(),
            'months'   => $months,
            'modes'    => CourseSessionModel::MODES,
            'statuses' => self::STATUSES,
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
        ]);
    }

    // ── One session ─────────────────────────────────────────────────────────

    public function create()
    {
        // The Courses screen links here as `course-sessions?course={id}`; the
        // "New date" button carries that through so adding a second date to a
        // course does not start with an empty dropdown.
        $preset = (int) $this->request->getGet('course');

        return view('Modules\Admin\Views\sessions\form', $this->formData(null) + [
            'title'        => 'New date',
            'active'       => 'course-sessions',
            'presetCourse' => $preset,
        ]);
    }

    public function show($id)
    {
        $id      = (int) $id;
        $session = (new CourseSessionModel())->detail($id);
        if ($session === null) {
            return redirect()->to(site_url('admin/course-sessions'))->with('error', 'That date no longer exists.');
        }

        $db     = db_connect();
        $days   = $session['days'] ?? [];
        $roster = (new EnrolmentModel())->roster($id);

        // The register, keyed [enrolment_id][session_day_id] so the view can ask
        // for one cell without walking the list for every learner on every day.
        $marks = [];
        if ($days !== [] && $roster !== []) {
            $rows = $db->table('attendance')
                ->whereIn('session_day_id', array_map('intval', array_column($days, 'id')))
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $marks[(int) $r['enrolment_id']][(int) $r['session_day_id']] = $r;
            }
        }

        return view('Modules\Admin\Views\sessions\show', $this->formData($session) + [
            'title'  => 'Date — ' . t_field($session['course_title'] ?? '{}'),
            'active' => 'course-sessions',
            'roster' => $roster,
            'marks'  => $marks,
            'attendanceStatuses' => self::ATTENDANCE,
            // Recomputed live rather than read from seats_reserved: the column
            // is a cache and expired holds may not have been swept yet, so this
            // is the number that is actually true right now.
            'seatsLeft' => (new InventoryService($db))->seatsLeft($id),
        ]);
    }

    // ── Saving ──────────────────────────────────────────────────────────────

    public function store()
    {
        return $this->persist(null);
    }

    public function update($id)
    {
        return $this->persist((int) $id);
    }

    private function persist(?int $id)
    {
        $rules = [
            'course_id'   => ['label' => 'Course', 'rules' => 'required|is_natural_no_zero'],
            'mode'        => ['label' => 'Delivery mode', 'rules' => 'required|in_list[' . implode(',', CourseSessionModel::MODES) . ']'],
            'status'      => ['label' => 'Status', 'rules' => 'required|in_list[' . implode(',', self::EDITABLE_STATUSES) . ']'],
            'timezone'    => ['label' => 'Timezone', 'rules' => 'required|max_length[64]'],
            'seats_total' => ['label' => 'Seats', 'rules' => 'required|is_natural'],
            'min_to_run'  => ['label' => 'Minimum to run', 'rules' => 'required|is_natural'],
            'start_date'  => ['label' => 'Start date', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'end_date'    => ['label' => 'End date', 'rules' => 'permit_empty|valid_date[Y-m-d]'],
            'meeting_url' => ['label' => 'Joining link', 'rules' => 'permit_empty|max_length[500]|valid_url_strict'],
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $model    = new CourseSessionModel();
        $existing = $id === null ? null : $model->find($id);
        if ($id !== null && $existing === null) {
            return redirect()->to(site_url('admin/course-sessions'))->with('error', 'That date no longer exists.');
        }

        $data   = $this->collect($existing);
        $errors = [];

        if ($data['start_date'] !== null && $data['end_date'] !== null && $data['end_date'] < $data['start_date']) {
            $errors[] = 'The end date is before the start date.';
        }
        // The one seat rule this screen enforces itself. seats_total is editable
        // and seats_sold is not, so the only way to create a chair that does not
        // exist is to lower the total under what has already been paid for.
        if ($existing !== null && $data['seats_total'] > 0 && $data['seats_total'] < (int) $existing['seats_sold']) {
            $errors[] = sprintf(
                'This date already has %d seat(s) sold, so the total cannot be set to %d. Move somebody to another date first, or refund them.',
                (int) $existing['seats_sold'],
                $data['seats_total']
            );
        }
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $db = db_connect();
        $db->transStart();

        if ($id === null) {
            $model->insert($data);
            $id = (int) $model->getInsertID();
        } else {
            $model->update($id, $data);
        }

        $locked  = $this->syncDays($id, $data);
        $unsold  = $this->syncPrices($id);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'The date could not be saved.');
        }

        // Outside the transaction, and after the day rows exist: refreshStatus
        // reads the seat counts and moves an `open` class to `confirmed` or
        // `full`. It leaves draft, running, completed and cancelled alone, so an
        // administrator's own choice is never overwritten by it.
        (new InventoryService($db))->refreshStatus($id);

        $notes = [];
        if ($this->linkError !== null) {
            $notes[] = $this->linkError;
        }
        if ($locked !== []) {
            $notes[] = 'Kept ' . implode(', ', $locked) . ': attendance has already been marked on those days, and removing them would take the register with them.';
        }
        if ($unsold) {
            $notes[] = 'This date has no price in any currency, so nobody can book it yet.';
        }

        $done = redirect()->to(site_url('admin/course-sessions/' . $id))->with('message', 'Date saved.');

        return $notes === [] ? $done : $done->with('error', implode(' ', $notes));
    }

    /**
     * The row to write, built from an explicit list.
     *
     * Not `$this->request->getPost()` and not a loop over the model's allowed
     * fields: `seats_sold` and `seats_reserved` are in `$allowedFields` because
     * InventoryService writes them through the model, and a payload built from
     * the request would carry whatever a form — or a crafted POST — happened to
     * contain. The list is the guard.
     */
    private function collect(?array $existing): array
    {
        $req = $this->request;

        // '' into a DATE or TIME column is a zero-date on MySQL and a silent
        // empty string on SQLite; both read back as "there is a date" and
        // neither is one. Nulled here, once, rather than at each reader.
        $blankToNull = static function (string $key) use ($req): ?string {
            $v = trim((string) $req->getPost($key));

            return $v === '' ? null : $v;
        };

        $status = (string) $req->getPost('status');
        if (! in_array($status, self::EDITABLE_STATUSES, true)) {
            $status = 'draft';
        }
        // A cancelled date is never reopened from a dropdown. Its learners were
        // cancelled with it and their refunds are in flight, so "open" would
        // advertise a class with an empty register. A new date is the honest
        // answer, and the cancellation reason stays readable on this one.
        if (($existing['status'] ?? '') === 'cancelled') {
            $status = 'cancelled';
        }

        $venue      = (int) $req->getPost('venue_id');
        $instructor = (int) $req->getPost('instructor_id');

        return [
            'course_id'        => (int) $req->getPost('course_id'),
            'mode'             => in_array((string) $req->getPost('mode'), CourseSessionModel::MODES, true)
                ? (string) $req->getPost('mode')
                : 'LIVE_ONLINE',
            'venue_id'         => $venue > 0 ? $venue : null,
            'instructor_id'    => $instructor > 0 ? $instructor : null,
            'language'         => (string) ($blankToNull('language') ?? 'en'),
            'start_date'       => $blankToNull('start_date'),
            'end_date'         => $blankToNull('end_date') ?? $blankToNull('start_date'),
            'timezone'         => (string) ($blankToNull('timezone') ?? 'Asia/Colombo'),
            'daily_start'      => $blankToNull('daily_start'),
            'daily_end'        => $blankToNull('daily_end'),
            'seats_total'      => max(0, (int) $req->getPost('seats_total')),
            'min_to_run'       => max(0, (int) $req->getPost('min_to_run')),
            'status'           => $status,
            'meeting_provider' => $blankToNull('meeting_provider'),
            'meeting_url_enc'  => $this->seal((string) $req->getPost('meeting_url'), $existing['meeting_url_enc'] ?? null),
            'notes'            => (string) $req->getPost('notes'),
            'is_private'       => $req->getPost('is_private') ? 1 : 0,
        ];
    }

    /**
     * Encrypt a joining link for storage.
     *
     * A blank field means "there is no link", not "keep the old one", because
     * the form is rendered with the decrypted value already in it — an empty box
     * is therefore something somebody cleared on purpose. When there is no
     * encryption key SecretBox refuses rather than storing plaintext, and the
     * right answer is to keep what was already there and say so, not to drop a
     * class's joining link because of a configuration problem.
     */
    private function seal(string $plain, ?string $existing): ?string
    {
        $plain = trim($plain);
        if ($plain === '') {
            return null;
        }

        try {
            return SecretBox::encrypt($plain);
        } catch (\RuntimeException $e) {
            $this->linkError = 'The joining link was not changed: ' . $e->getMessage();

            return $existing;
        }
    }

    /**
     * Bring `session_days` into line with what was submitted.
     *
     * Matched by date and updated in place. The obvious implementation — delete
     * every row for the session and insert the new list — passes every test on
     * an empty database and destroys the attendance register the first time it
     * runs on a class that has already been taught, because `attendance` points
     * at `session_days.id`.
     *
     * @return list<string> dates that could not be removed because they carry a register
     */
    private function syncDays(int $sessionId, array $session): array
    {
        $db     = db_connect();
        $posted = $this->request->getPost('days');
        $wanted = [];

        if (is_array($posted)) {
            foreach ($posted as $row) {
                $date = trim((string) ($row['date'] ?? ''));
                if ($date === '') {
                    continue;
                }
                $wanted[$date] = [
                    'start' => trim((string) ($row['start'] ?? '')) ?: $session['daily_start'],
                    'end'   => trim((string) ($row['end'] ?? '')) ?: $session['daily_end'],
                    'url'   => trim((string) ($row['url'] ?? '')),
                ];
            }
        }

        // A taught class with no day rows has no register and no calendar file,
        // so a contiguous range is filled in for it. Only contiguous days can be
        // guessed — a weekend course running over two weekends is typed in by
        // hand, which is what the editor on the form is for.
        if ($wanted === [] && ! empty($session['start_date'])) {
            $cursor = new \DateTimeImmutable((string) $session['start_date']);
            $last   = new \DateTimeImmutable((string) ($session['end_date'] ?? $session['start_date']));
            while ($cursor <= $last && count($wanted) < 60) {
                $wanted[$cursor->format('Y-m-d')] = [
                    'start' => $session['daily_start'],
                    'end'   => $session['daily_end'],
                    'url'   => '',
                ];
                $cursor = $cursor->modify('+1 day');
            }
        }

        ksort($wanted);

        $existing = $db->table('session_days')->where('session_id', $sessionId)->get()->getResultArray();
        $byDate   = array_column($existing, null, 'day_date');

        $sort = 0;
        foreach ($wanted as $date => $row) {
            $prior   = $byDate[$date] ?? null;
            $payload = [
                'session_id'      => $sessionId,
                'day_date'        => $date,
                'start_time'      => $row['start'] ?: null,
                'end_time'        => $row['end'] ?: null,
                'meeting_url_enc' => $this->seal($row['url'], $prior['meeting_url_enc'] ?? null),
                'sort_order'      => ++$sort,
            ];

            if ($prior === null) {
                $db->table('session_days')->insert($payload);
            } else {
                $db->table('session_days')->where('id', (int) $prior['id'])->update($payload);
            }
        }

        $locked = [];
        foreach ($existing as $prior) {
            if (isset($wanted[$prior['day_date']])) {
                continue;
            }
            if ($db->table('attendance')->where('session_day_id', (int) $prior['id'])->countAllResults() > 0) {
                $locked[] = (string) $prior['day_date'];
                continue;
            }
            $db->table('session_days')->where('id', (int) $prior['id'])->delete();
        }

        return $locked;
    }

    /**
     * Write one price per active currency.
     *
     * Published per currency and never converted at runtime: an FX-converted
     * figure produces Rs 47,382 on a page and loses margin control the day the
     * rate moves.
     *
     * Only active currencies are walked, so a price left behind by a currency
     * that has since been switched off is preserved rather than deleted — the
     * currency may well be switched back on, and losing a price book because of
     * a settings change is not a trade this screen is entitled to make.
     *
     * @return bool whether the session ended up with no price at all
     */
    private function syncPrices(int $sessionId): bool
    {
        helper('commerce');
        $db     = db_connect();
        $posted = (array) $this->request->getPost('prices');
        $priced = 0;

        foreach (currency_options() as $currency) {
            $code    = strtoupper($currency['code']);
            $price   = $this->minorUnits($posted[$code]['price'] ?? null);
            $compare = $this->minorUnits($posted[$code]['compare'] ?? null);
            $row     = $db->table('session_prices')
                ->where('session_id', $sessionId)->where('currency', $code)
                ->get()->getRowArray();

            if ($price === null) {
                // Cleared on purpose: this session is simply not sold in this
                // currency, and a zero row would advertise it as free.
                if ($row !== null) {
                    $db->table('session_prices')->where('id', (int) $row['id'])->delete();
                }
                continue;
            }

            $priced++;
            $payload = [
                'session_id'       => $sessionId,
                'currency'         => $code,
                'price_cents'      => $price,
                'compare_at_cents' => $compare !== null && $compare > $price ? $compare : null,
            ];

            if ($row === null) {
                $db->table('session_prices')->insert($payload);
            } else {
                $db->table('session_prices')->where('id', (int) $row['id'])->update($payload);
            }
        }

        return $priced === 0;
    }

    /**
     * Read a price field.
     *
     * The field is in minor units — 42500, never 425.00 — and stays an integer
     * from the input box to the column. Accepting a decimal here would put a
     * float between the two, and a float is how a price becomes 42499.
     */
    private function minorUnits(mixed $raw): ?int
    {
        $digits = preg_replace('/\D/', '', (string) $raw);

        return $digits === '' ? null : (int) $digits;
    }

    // ── The register ────────────────────────────────────────────────────────

    /**
     * Mark one day of one class.
     *
     * Per day rather than per class: a corporate buyer paying for four staff on
     * a two-day course wants to know which of them turned up on day two, and the
     * certificate rule is a percentage of days attended.
     */
    public function attendance($id)
    {
        $id      = (int) $id;
        $db      = db_connect();
        $session = (new CourseSessionModel())->find($id);
        if ($session === null) {
            return redirect()->to(site_url('admin/course-sessions'))->with('error', 'That date no longer exists.');
        }

        // The day must belong to this session. Without this the register of one
        // class could be marked from another class's screen by editing a hidden
        // field, and the register is what a certificate is issued on.
        $day = $db->table('session_days')
            ->where('id', (int) $this->request->getPost('day_id'))
            ->where('session_id', $id)
            ->get()->getRowArray();
        if ($day === null) {
            return redirect()->to(site_url('admin/course-sessions/' . $id))->with('error', 'That day is not part of this date.');
        }

        $posted   = (array) $this->request->getPost('status');
        $minutes  = (array) $this->request->getPost('minutes');
        $markedBy = (int) (session()->get('admin_user')['id'] ?? 0) ?: null;
        $register = new AttendanceModel();
        $marked   = 0;

        // Driven by the roster, not by what was posted: only somebody actually
        // enrolled on this session may appear in its register, whatever ids a
        // form was persuaded to submit.
        foreach ((new EnrolmentModel())->roster($id) as $enrolment) {
            $enrolmentId = (int) $enrolment['id'];
            $status      = (string) ($posted[$enrolmentId] ?? '');
            if (! in_array($status, self::ATTENDANCE, true)) {
                continue;
            }

            $mins = trim((string) ($minutes[$enrolmentId] ?? ''));
            $register->mark($enrolmentId, (int) $day['id'], $status, $mins === '' ? null : (int) $mins, $markedBy);
            $marked++;
        }

        return redirect()->to(site_url('admin/course-sessions/' . $id) . '#register')
            ->with('message', sprintf('Register marked for %s — %d learner(s).', date('j M Y', strtotime((string) $day['day_date'])), $marked));
    }

    // ── Cancelling ──────────────────────────────────────────────────────────

    /**
     * Cancel a date, and everybody on it, in one action.
     *
     * The policy promises a school-initiated cancellation is handled for the
     * learner rather than by them, so this is one button and a reason rather
     * than a checklist an administrator works through on the morning eleven
     * people were expecting to travel. EnrolmentService does the whole of it in
     * a transaction and hands the seats back.
     */
    public function cancel($id)
    {
        $id      = (int) $id;
        $session = (new CourseSessionModel())->find($id);
        if ($session === null) {
            return redirect()->to(site_url('admin/course-sessions'))->with('error', 'That date no longer exists.');
        }
        if ($session['status'] === 'cancelled') {
            return redirect()->to(site_url('admin/course-sessions/' . $id))->with('error', 'This date is already cancelled.');
        }

        // Required, and stored on the row: it is what the learner is told, and
        // what somebody reading the schedule in six months needs in order to
        // understand why a date has no register.
        $reason = trim((string) $this->request->getPost('reason'));
        if ($reason === '') {
            return redirect()->to(site_url('admin/course-sessions/' . $id))
                ->with('error', 'A cancellation needs a reason — it is shown to everybody who was booked.');
        }

        $result = (new EnrolmentService())->cancelSession($id, $reason);

        return redirect()->to(site_url('admin/course-sessions/' . $id))->with(
            'message',
            $result['cancelled'] === 0
                ? 'Date cancelled. Nobody was booked on it.'
                : sprintf(
                    'Date cancelled and %d enrolment(s) with it. The seats have been returned; refunds are still to be issued from Orders.',
                    $result['cancelled']
                )
        );
    }

    // ── Shared view data ────────────────────────────────────────────────────

    /**
     * Everything both the new-date screen and the session screen need.
     *
     * The form is one file used twice — standalone for a new date, embedded in
     * the session screen for an existing one — so there is a single place to
     * change when a column is added, and no chance of the two drifting.
     */
    private function formData(?array $session): array
    {
        helper(['norlanka', 'commerce']);
        $db = db_connect();

        $prices = [];
        $days   = [];

        if ($session !== null) {
            foreach ($db->table('session_prices')->where('session_id', (int) $session['id'])->get()->getResultArray() as $p) {
                $prices[strtoupper((string) $p['currency'])] = $p;
            }
            foreach ($session['days'] ?? (new CourseSessionModel())->days((int) $session['id']) as $day) {
                $day['meeting_url'] = SecretBox::decrypt($day['meeting_url_enc'] ?? null);
                $days[]             = $day;
            }
        }

        return [
            'row'         => $session,
            'courses'     => $this->courseOptions(),
            'venues'      => (new VenueModel())->orderBy('name', 'ASC')->findAll(),
            'instructors' => (new InstructorModel())->orderBy('name', 'ASC')->findAll(),
            'currencies'  => currency_options(),
            'prices'      => $prices,
            'dayRows'     => $days,
            'modes'       => CourseSessionModel::MODES,
            'statuses'    => self::EDITABLE_STATUSES,
            'locales'     => config('App')->supportedLocales,
            // Shown, not hidden behind "a link is set". An SMTP password is
            // somebody else's credential and belongs off the page; a joining
            // link is this class's front door and the person running the class
            // has to be able to read it, check it and paste it into an email.
            'joiningUrl'  => $session === null ? '' : SecretBox::decrypt($session['meeting_url_enc'] ?? null),
        ];
    }

    /**
     * Courses for a dropdown, ordered by the title an editor actually reads.
     *
     * `title` is a JSON locale map, so ordering in SQL would sort by whichever
     * language happens to be first in the JSON. Thirty-two rows sort faster in
     * PHP than the confusion costs.
     *
     * @return list<array>
     */
    private function courseOptions(): array
    {
        helper('norlanka');

        $rows = (new CourseModel())->select('id, slug, title, pillar, status')->findAll();
        usort($rows, static fn (array $a, array $b): int => strcasecmp(t_field($a['title']), t_field($b['title'])));

        return $rows;
    }
}
