<?php

namespace Modules\Account\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Auth\Models\UserModel;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Commerce\Controllers\Corporate;
use Modules\Commerce\Models\OrderModel;
use Modules\Core\Libraries\SecretBox;
use Modules\Learning\Models\CertificateModel;
use Modules\Learning\Models\EnrolmentModel;
use Modules\Learning\Models\ProgressModel;
use Modules\Learning\Models\RescheduleRequestModel;
use Modules\Learning\Services\CertificateService;

/**
 * The learner's account: what they bought, when it happens, how to get in, and
 * what they can take away afterwards.
 *
 * Four decisions are encoded here, and every one of them is about who is
 * allowed to see what.
 *
 *   **Signed in is not the same as booked.** The `learner` filter answers "who
 *   is asking"; it does not answer "is this theirs". Every page below that
 *   shows something bought re-asks the second question against `enrolments`,
 *   `orders` or `certificates` — never against the id in the address bar. The
 *   joining page is the sharp end of that: `meeting_url_enc` is a key to a paid
 *   classroom, and a page that decrypts it for anybody with a session cookie
 *   has given the class away.
 *
 *   **A failed ownership check is a 404, not a 403.** `/account/invoices/812`
 *   answering "forbidden" tells a stranger that invoice 812 exists and roughly
 *   how many the school has issued. It answers "no such page" instead, which is
 *   the same thing this codebase already does for orders.
 *
 *   **Nothing here fulfils, marks paid, issues, or touches a seat.** This is a
 *   window onto records that `EnrolmentService`, `CertificateService` and
 *   `InventoryService` created. `requestTransfer()` writes a *request* for an
 *   administrator to decide; it moves nobody between classes and it does not go
 *   near `seats_sold`.
 *
 *   **Certificates and invoices are streamed, never linked.** A certificate PDF
 *   carries somebody's full name and what they studied, so it lives under
 *   `writable/` and reaches the browser through `certificate()`, which checks
 *   who is asking first. Served from `public/` it would be one guessed filename
 *   away from anybody and indexable by whatever found a link to it.
 */
class Dashboard extends BaseController
{
    /**
     * Enrolment statuses the account area shows.
     *
     * `cancelled` and `transferred` are in the list deliberately. A learner
     * whose class the school called off, or who was moved to another date, has
     * a booking that stopped existing — and a booking that silently disappears
     * from the account is a support email, not a tidy page.
     */
    private const VISIBLE_STATUSES = ['active', 'completed', 'cancelled', 'transferred'];

    /** Minimum length for a password set from the profile form. */
    private const MIN_PASSWORD = 10;

    // ── Overview ────────────────────────────────────────────────────────────

    /**
     * What is coming up, what is half finished, and what needs doing.
     *
     * Three questions in that order, because that is the order a learner has
     * them in: am I supposed to be somewhere this week, where had I got to, and
     * is anything wrong. Everything else is a click away in the sidebar.
     */
    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $userId     = (int) LearnerAuth::id();
        $user       = LearnerAuth::record();
        $enrolments = new EnrolmentModel();

        $upcoming  = $enrolments->upcomingFor($userId);
        $selfPaced = $enrolments->selfPacedFor($userId);

        // The percentage costs two counts per course. That is fine for the
        // handful of courses one learner owns and would not be fine on a
        // listing; if a learner ever holds fifty, this is the loop to fold into
        // a single grouped query rather than the place to add a cache.
        $progress = new ProgressModel();
        foreach ($selfPaced as &$enrolment) {
            $enrolment['percent'] = $progress->percent($userId, (int) $enrolment['course_id']);
        }
        unset($enrolment);

        return view('Modules\Account\Views\dashboard\index', [
            'current'         => 'overview',
            'user'            => $user,
            'upcoming'        => $upcoming,
            'selfPaced'       => $selfPaced,
            'attention'       => $this->attentionFor($userId, $user),
            'crumbs'          => [['label' => lang('Account.nav.title')]],
            'title'           => lang('Account.dashboard.title') . ' — ' . setting('site_name', ''),
            // An account page has nothing to offer a crawler and a great deal to
            // lose by being indexed.
            'noIndex'         => true,
        ]);
    }

    // ── Every enrolment ─────────────────────────────────────────────────────

    /**
     * The whole list: upcoming dates, self-paced study, and everything past.
     *
     * Read in one query and partitioned in PHP rather than asked for three
     * times. `EnrolmentModel::forUser()` already joins the course, the session
     * and the venue, so the difference between the three groups is a date
     * comparison and not a trip to the database.
     */
    public function courses(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $userId = (int) LearnerAuth::id();
        $rows   = (new EnrolmentModel())->forUser($userId, self::VISIBLE_STATUSES);

        $today     = date('Y-m-d');
        $upcoming  = [];
        $selfPaced = [];
        $past      = [];

        $progress = new ProgressModel();

        foreach ($rows as $row) {
            // A row with no start date is self-paced study, which is available
            // now rather than upcoming. Getting this the wrong way round files
            // the entire on-demand library under "coming up", where it never
            // leaves.
            if (empty($row['start_date'])) {
                $row['percent'] = $progress->percent($userId, (int) $row['course_id']);
                $selfPaced[]    = $row;

                continue;
            }

            // A cancelled booking is history whatever its dates say: it is not
            // something to turn up to.
            if ($row['start_date'] >= $today && in_array($row['status'], ['active', 'completed'], true)) {
                $upcoming[] = $row;

                continue;
            }

            $past[] = $row;
        }

        // Most recent first for what has already happened; soonest first for
        // what has not. `forUser()` orders ascending, which is right for one
        // list and backwards for the other.
        $past = array_reverse($past);

        return view('Modules\Account\Views\dashboard\courses', [
            'current'         => 'courses',
            'upcoming'        => $upcoming,
            'selfPaced'       => $selfPaced,
            'past'            => $past,
            'crumbs'          => [
                ['label' => lang('Account.nav.title'), 'url' => locale_url('account')],
                ['label' => lang('Account.courses.title')],
            ],
            'title'           => lang('Account.courses.title') . ' — ' . setting('site_name', ''),
            'noIndex'         => true,
        ]);
    }

    // ── One booked class ────────────────────────────────────────────────────

    /**
     * The joining page for a class this learner is actually on.
     *
     * The ownership check is the first thing that happens and the reason this
     * method exists at all: the session id is a number in a URL, and the
     * decrypted meeting link below it is the thing somebody would guess ids to
     * find. No enrolment, no page — and a 404 rather than a refusal, so the
     * address does not confirm which sessions run.
     *
     * Times are shown twice, in the class's own zone and in the learner's. A
     * live class sold across Colombo, Dubai and London has three clocks around
     * it, and a page that prints one of them is a page somebody joins an hour
     * late. When the profile carries no timezone the second clock is UTC and
     * the page says so, rather than guessing from a browser header and being
     * quietly wrong for anybody travelling.
     */
    public function live(?string $locale = null, ?string $sessionId = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $userId    = (int) LearnerAuth::id();
        $sessionId = (int) $sessionId;

        $enrolment = (new EnrolmentModel())
            ->where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->whereIn('status', ['active', 'completed', 'transferred'])
            ->orderBy('id', 'ASC')
            ->first();

        if ($enrolment === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $sessions = new CourseSessionModel();
        $session  = $sessions->detail($sessionId);
        if ($session === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $courseId = (int) $session['course_id'];
        $db       = db_connect();
        $online   = in_array((string) $session['mode'], ['LIVE_ONLINE', 'PRIVATE'], true);

        // Decrypted here, once, and only for a class that has one. SecretBox
        // returns an empty string when the key has changed or was never set,
        // which reads as "not configured" — the truthful answer, and the view
        // says the link is still to come rather than printing nothing.
        $joinUrl = $online ? $this->safeUrl(SecretBox::decrypt($session['meeting_url_enc'] ?? null)) : '';

        // The ciphertext itself never reaches the view. There is no reason for
        // a template to hold it and every reason for it not to end up in a
        // debug dump or a cached fragment.
        unset($session['meeting_url_enc']);

        $days = [];
        foreach ($session['days'] as $day) {
            $day['join_url'] = $online ? $this->safeUrl(SecretBox::decrypt($day['meeting_url_enc'] ?? null)) : '';
            unset($day['meeting_url_enc']);
            $days[] = $day;
        }
        $session['days'] = $days;

        // What to bring, and what to have installed. The course's own
        // prerequisites rather than a generic list, because "Photoshop 2026
        // installed and signed in" is the thing that actually goes wrong at
        // 09:00.
        $prepare = $db->table('course_prerequisites')
            ->where('course_id', $courseId)
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();

        // Downloadable material hangs off lessons, so a taught course and its
        // on-demand recording share one library rather than keeping two.
        $materials = $db->table('lesson_assets la')
            ->select('la.id, la.label, la.kind, la.size_bytes, l.slug AS lesson_slug')
            ->join('lessons l', 'l.id = la.lesson_id')
            ->where('l.course_id', $courseId)
            ->where('l.status', 'published')
            ->orderBy('l.sort_order', 'ASC')->orderBy('la.sort_order', 'ASC')
            ->get()->getResultArray();

        $hasLessons = $db->table('lessons')
            ->where('course_id', $courseId)->where('status', 'published')
            ->countAllResults() > 0;

        // Dates this booking could move to: the same course, still on sale,
        // still ahead, and not the one they are already on.
        $alternatives = array_values(array_filter(
            $sessions->forCourse($courseId, null, 12),
            static fn (array $row): bool => (int) $row['id'] !== $sessionId
        ));

        $currency = $this->currencyFor($enrolment);

        return view('Modules\Account\Views\dashboard\live', [
            'current'      => 'courses',
            'enrolment'    => $enrolment,
            'session'      => $session,
            'joinUrl'      => $joinUrl,
            'online'       => $online,
            'prepare'      => $prepare,
            'materials'    => $materials,
            'hasLessons'   => $hasLessons,
            'alternatives' => $alternatives,
            // The fee is decided by how much notice is being given on the class
            // they are leaving, never by the date they are asking for. A learner
            // eight days out does not get a free move by picking a date in
            // March.
            'transferFree' => RescheduleRequestModel::isFree($session['start_date'] ?? null),
            'transferFee'  => $this->transferFee($currency),
            'currency'     => $currency,
            'pending'      => (new RescheduleRequestModel())
                ->where('enrolment_id', (int) $enrolment['id'])
                ->where('status', 'requested')->first(),
            'learnerTz'    => $this->learnerTimezone(),
            'crumbs'       => [
                ['label' => lang('Account.nav.title'), 'url' => locale_url('account')],
                ['label' => lang('Account.courses.title'), 'url' => locale_url('account/courses')],
                ['label' => t_field($session['course_title'])],
            ],
            'title'        => t_field($session['course_title']) . ' — ' . lang('Account.live.title'),
            'noIndex'      => true,
        ]);
    }

    // ── Certificates ────────────────────────────────────────────────────────

    public function certificates(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        return view('Modules\Account\Views\dashboard\certificates', [
            'current'         => 'certificates',
            'certificates'    => (new CertificateModel())->forUser((int) LearnerAuth::id()),
            'crumbs'          => [
                ['label' => lang('Account.nav.title'), 'url' => locale_url('account')],
                ['label' => lang('Account.certificates.title')],
            ],
            'title'           => lang('Account.certificates.title') . ' — ' . setting('site_name', ''),
            'noIndex'         => true,
        ]);
    }

    /**
     * The download.
     *
     * Ownership before bytes, always. The file lives under `writable/` — see
     * `CertificateService::pathFor()` — so this method is the only route to it,
     * and the check here is the whole of the access control.
     *
     * A revoked certificate is not handed over. The record stays, and
     * `/verify/{code}` keeps answering with "revoked", which is the answer
     * whoever is checking it needs; handing back a PDF that looks valid would
     * put a withdrawn document back into circulation.
     */
    public function certificate(?string $locale = null, ?string $id = null)
    {
        helper(['norlanka', 'url']);

        $certificate = (new CertificateModel())->find((int) $id);

        if ($certificate === null || (int) $certificate['user_id'] !== (int) LearnerAuth::id()) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! empty($certificate['revoked_at'])) {
            return redirect()->to(locale_url('account/certificates'))
                ->with('error', lang('Account.certificates.revoked_note'));
        }

        try {
            $pdf = (new CertificateService())->pdf($certificate);
        } catch (\Throwable $e) {
            // Rendering a PDF can fail for reasons that have nothing to do with
            // this learner — a missing font, a full disk. They get a message
            // they can act on rather than a stack trace, and the log gets the
            // serial so it can be regenerated.
            log_message('error', 'Certificate download failed for {serial}: {msg}', [
                'serial' => $certificate['serial'],
                'msg'    => $e->getMessage(),
            ]);

            return redirect()->to(locale_url('account/certificates'))
                ->with('error', lang('Account.certificates.download_failed'));
        }

        // The serial is ours and already matches this pattern; it is stripped
        // anyway because it is being interpolated into a response header, and a
        // header built from stored text is a header worth not trusting.
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '', (string) $certificate['serial']) ?: 'certificate';

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '.pdf"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($pdf);
    }

    // ── Invoices ────────────────────────────────────────────────────────────

    /**
     * Everything this learner has been billed for.
     *
     * An order with no row in `invoices` yet prints as a proforma against its
     * order number, through the commerce route that already knows how. The
     * invoice series is gapless accounting issued once at fulfilment; minting a
     * number for an order that may never be paid would put a hole in it.
     */
    public function invoices(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $orders = (new OrderModel())->forUser((int) LearnerAuth::id());

        // One query for the invoice rows rather than one per order.
        $byOrder = [];
        if ($orders !== []) {
            $rows = db_connect()->table('invoices')
                ->whereIn('order_id', array_map('intval', array_column($orders, 'id')))
                ->get()->getResultArray();
            $byOrder = array_column($rows, null, 'order_id');
        }

        foreach ($orders as &$order) {
            $order['invoice'] = $byOrder[$order['id']] ?? null;
        }
        unset($order);

        return view('Modules\Account\Views\dashboard\invoices', [
            'current'         => 'invoices',
            'orders'          => $orders,
            'crumbs'          => [
                ['label' => lang('Account.nav.title'), 'url' => locale_url('account')],
                ['label' => lang('Account.invoices.title')],
            ],
            'title'           => lang('Account.invoices.title') . ' — ' . setting('site_name', ''),
            'noIndex'         => true,
        ]);
    }

    /**
     * One invoice, as the printable A4 document.
     *
     * The same ownership rule as the certificate, and the same 404. The
     * document itself is the commerce view: an invoice does not become a
     * different document because it was reached from the account area, and a
     * second copy of that template is a second place for the tax line to go
     * wrong.
     */
    public function invoice(?string $locale = null, ?string $id = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $invoice = db_connect()->table('invoices')->where('id', (int) $id)->get()->getRowArray();
        if ($invoice === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $orders = new OrderModel();
        $order  = $orders->find((int) $invoice['order_id']);

        // A guest order has a null user_id, which casts to 0 and can never
        // match a signed-in learner. That is the intended outcome: a guest
        // order is reached with the cart token, through the commerce route.
        if ($order === null || (int) ($order['user_id'] ?? 0) !== (int) LearnerAuth::id()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $billing = json_decode((string) $order['billing_json'], true);

        return view('Modules\Commerce\Views\checkout\invoice', [
            'order'   => $order,
            'items'   => $orders->items((int) $order['id']),
            'invoice' => $invoice,
            'billing' => is_array($billing) ? $billing : [],
        ], ['saveData' => false]);
    }

    // ── Profile ─────────────────────────────────────────────────────────────

    public function profile(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        return view('Modules\Account\Views\dashboard\profile', [
            'current'         => 'profile',
            'user'            => LearnerAuth::record(),
            'countries'       => Corporate::COUNTRIES,
            'timezones'       => $this->timezoneGroups(),
            'locales'         => supported_locales(),
            'minPassword'     => self::MIN_PASSWORD,
            'errors'          => session()->getFlashdata('errors') ?? [],
            'crumbs'          => [
                ['label' => lang('Account.nav.title'), 'url' => locale_url('account')],
                ['label' => lang('Account.profile.title')],
            ],
            'title'           => lang('Account.profile.title') . ' — ' . setting('site_name', ''),
            'noIndex'         => true,
        ]);
    }

    /**
     * Save the profile, and optionally change the password.
     *
     * Two concerns in one form on purpose — a learner correcting their timezone
     * should not have to find a second page to change a password — but they are
     * saved independently: a wrong current password rejects the password change
     * and nothing else, and the details they typed come back with them.
     *
     * The email address is deliberately not editable here. Changing it means
     * re-verifying it, and an account whose address can be swapped from a
     * signed-in session is an account takeover that survives a password reset.
     */
    public function saveProfile(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $userId = (int) LearnerAuth::id();
        $users  = new UserModel();
        $user   = $users->find($userId);
        if ($user === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        // The three code fields are checked below against their real lists
        // rather than through in_list: four hundred timezone identifiers in a
        // validation string is a rule nobody can read and nobody will maintain.
        $rules = [
            'first_name'           => ['label' => lang('Account.profile.first_name'), 'rules' => 'required|max_length[64]'],
            'last_name'            => ['label' => lang('Account.profile.last_name'), 'rules' => 'permit_empty|max_length[64]'],
            'phone'                => ['label' => lang('Account.profile.phone'), 'rules' => 'permit_empty|max_length[48]'],
            'company'              => ['label' => lang('Account.profile.company'), 'rules' => 'permit_empty|max_length[191]'],
            'job_title'            => ['label' => lang('Account.profile.job_title'), 'rules' => 'permit_empty|max_length[128]'],
            'new_password'         => ['label' => lang('Account.profile.new_password'), 'rules' => 'permit_empty|min_length[' . self::MIN_PASSWORD . ']|max_length[255]'],
            'new_password_confirm' => ['label' => lang('Account.profile.confirm_password'), 'rules' => 'permit_empty|matches[new_password]'],
            'current_password'     => ['label' => lang('Account.profile.current_password'), 'rules' => 'required_with[new_password]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $post = fn (string $key): string => trim((string) ($this->request->getPost($key) ?? ''));

        $country  = strtoupper($post('country'));
        $timezone = $post('timezone');
        $chosen   = $post('locale');

        $update = [
            'first_name' => mb_substr($post('first_name'), 0, 64),
            'last_name'  => mb_substr($post('last_name'), 0, 64),
            'phone'      => mb_substr($post('phone'), 0, 48) ?: null,
            'company'    => mb_substr($post('company'), 0, 191) ?: null,
            'job_title'  => mb_substr($post('job_title'), 0, 128) ?: null,
            // An unrecognised code is dropped rather than stored. These three
            // decide which price book the learner sees, what time a reminder
            // says the class starts and which language a certificate is issued
            // in, and a junk value in any of them is wrong in a way nobody
            // notices for months.
            'country'    => isset(Corporate::COUNTRIES[$country]) ? $country : null,
            'timezone'   => in_array($timezone, \DateTimeZone::listIdentifiers(), true) ? $timezone : null,
            'locale'     => in_array($chosen, config('App')->supportedLocales, true) ? $chosen : null,
        ];

        // Consent is recorded with the moment it was given, because both the
        // PDPA and the GDPR ask you to be able to show *when* — which a boolean
        // alone cannot answer. Withdrawal clears the flag and keeps the date,
        // since it is still the evidence for everything sent before today.
        $optIn = $this->request->getPost('marketing_opt_in') !== null;
        $update['marketing_opt_in'] = $optIn ? 1 : 0;
        if ($optIn && (int) ($user['marketing_opt_in'] ?? 0) === 0) {
            $update['consent_at'] = date('Y-m-d H:i:s');
        }

        $newPassword     = (string) ($this->request->getPost('new_password') ?? '');
        $changedPassword = false;

        if ($newPassword !== '') {
            if (! password_verify((string) ($this->request->getPost('current_password') ?? ''), (string) $user['password_hash'])) {
                return redirect()->back()->withInput()
                    ->with('errors', ['current_password' => lang('Account.profile.err_current_password')]);
            }

            $update['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            // Any outstanding reset link is torn up in the same write. A link
            // that still works after its owner has changed their password is a
            // way back in for whoever asked for it.
            $update['reset_token']      = null;
            $update['reset_expires_at'] = null;
            $changedPassword            = true;
        }

        $users->update($userId, $update);

        $fresh = $users->find($userId);

        if ($changedPassword) {
            // A new session id on a credential change, so a cookie captured
            // before the change cannot be used after it. Regenerate first, then
            // rewrite the stored copy, matching LearnerAuth::login().
            session()->regenerate(true);
        }

        // The session carries a copy of the display name for the header, so it
        // has to be refreshed here or the page still greets them by their old
        // one. Set directly rather than through LearnerAuth::login(), which
        // would also stamp last_login_at — and saving a profile is not a login.
        session()->set(LearnerAuth::SESSION_KEY, [
            'id'       => $userId,
            'email'    => (string) $fresh['email'],
            'name'     => LearnerAuth::displayName($fresh),
            'verified' => ! empty($fresh['email_verified_at']),
        ]);

        // A changed interface language has to change the URL as well, or the
        // learner saves "Sinhala" and stays on an English page wondering
        // whether it worked.
        $target = $update['locale'] ?? current_locale();

        return redirect()->to(locale_url('account/profile', $target))
            ->with('notice', $changedPassword
                ? lang('Account.profile.saved_with_password')
                : lang('Account.profile.saved'));
    }

    // ── Transfers ───────────────────────────────────────────────────────────

    /**
     * Ask to move a booking to another date.
     *
     * This writes a row in `reschedule_requests` and nothing else. It does not
     * change the enrolment, it does not hold a seat on the requested date, and
     * it goes nowhere near `seats_sold` — moving somebody between two classes
     * is an inventory operation, and inventory belongs to `InventoryService`
     * under a lock. An administrator decides, and the move happens there.
     *
     * The fee is decided by `RescheduleRequestModel::isFree()` on the date they
     * are **leaving**, never on the date they are asking for. A learner eight
     * days out from a class does not earn a free transfer by nominating a date
     * in March, and a policy applied any other way is a policy two customers
     * get told two different versions of.
     */
    public function requestTransfer(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $userId    = (int) LearnerAuth::id();
        $enrolment = (new EnrolmentModel())
            ->where('id', (int) $this->request->getPost('enrolment_id'))
            ->where('user_id', $userId)
            ->first();

        // Not theirs, or not a booking that can move. Back to the list rather
        // than a 404: this is a POST from a form, and the honest outcome is a
        // message on a page they can see.
        if ($enrolment === null || $enrolment['status'] !== 'active' || empty($enrolment['session_id'])) {
            return redirect()->to(locale_url('account/courses'))
                ->with('error', lang('Account.transfer.err_not_transferable'));
        }

        $fromSessionId = (int) $enrolment['session_id'];
        $back          = redirect()->to(locale_url('account/live/' . $fromSessionId));

        $requests = new RescheduleRequestModel();
        if ($requests->where('enrolment_id', (int) $enrolment['id'])->where('status', 'requested')->first() !== null) {
            // One open request at a time. Two rows for the same booking is two
            // administrators making two different decisions about it.
            return $back->with('notice', lang('Account.transfer.already_requested'));
        }

        $db   = db_connect();
        $from = $db->table('course_sessions')->where('id', $fromSessionId)->get()->getRowArray();
        if ($from === null) {
            return $back->with('error', lang('Account.transfer.err_not_transferable'));
        }

        $toSessionId = (int) ($this->request->getPost('to_session_id') ?? 0);
        if ($toSessionId > 0 && ! $this->isTransferTarget($toSessionId, (int) $from['course_id'], $fromSessionId)) {
            // A date that is not on sale, is in the past, is private, or belongs
            // to another course entirely. Silently accepting it would produce a
            // request an administrator cannot act on.
            return $back->withInput()->with('error', lang('Account.transfer.err_bad_date'));
        }

        $currency = $this->currencyFor($enrolment);
        $free     = RescheduleRequestModel::isFree($from['start_date'] ?? null);
        $fee      = $this->transferFee($currency);

        $requests->insert([
            'enrolment_id'    => (int) $enrolment['id'],
            'from_session_id' => $fromSessionId,
            // Null means "any later date" — a learner who knows they cannot make
            // this one but has not chosen the next. Forcing a choice here loses
            // the request and gains nothing.
            'to_session_id'   => $toSessionId ?: null,
            'status'          => 'requested',
            // Zero when the notice period is met. When it is not, the published
            // fee for this order's currency — or zero when none is configured,
            // because inventing a charge on a page somebody will be billed
            // against is worse than asking an administrator to set one.
            'fee_cents'       => $free ? 0 : (int) ($fee ?? 0),
            'currency'        => $currency,
            'reason'          => mb_substr(trim((string) ($this->request->getPost('reason') ?? '')), 0, 1000) ?: null,
            'requested_at'    => date('Y-m-d H:i:s'),
        ]);

        return $back->with('notice', $free
            ? lang('Account.transfer.sent_free')
            : lang('Account.transfer.sent_fee'));
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * The things on this account that want doing.
     *
     * Deliberately only facts already in the database: an unverified address, an
     * order still waiting for money, a class the school called off, a transfer
     * nobody has decided yet. Nothing here is a nudge or a suggestion — an
     * account page that manufactures tasks trains people to ignore it.
     *
     * @param array|null $user the row already read by the caller, so the page
     *                          does not fetch the same user twice
     * @return array{unverified:bool, unpaid:list<array>, cancelled:list<array>, transfers:list<array>}
     */
    private function attentionFor(int $userId, ?array $user): array
    {
        $unpaid = array_values(array_filter(
            (new OrderModel())->forUser($userId),
            static fn (array $order): bool => $order['status'] === 'pending_payment'
        ));

        $cancelled = array_values(array_filter(
            (new EnrolmentModel())->forUser($userId, ['active']),
            static fn (array $row): bool => ($row['session_status'] ?? '') === 'cancelled'
        ));

        $transfers = db_connect()->table('reschedule_requests rr')
            ->select('rr.*, c.title AS course_title, cs.start_date')
            ->join('enrolments e', 'e.id = rr.enrolment_id')
            ->join('courses c', 'c.id = e.course_id', 'left')
            ->join('course_sessions cs', 'cs.id = rr.from_session_id', 'left')
            ->where('e.user_id', $userId)
            ->where('rr.status', 'requested')
            ->orderBy('rr.requested_at', 'DESC')
            ->get()->getResultArray();

        return [
            'unverified' => $user !== null && empty($user['email_verified_at']),
            'unpaid'     => $unpaid,
            'cancelled'  => $cancelled,
            'transfers'  => $transfers,
        ];
    }

    /**
     * Whether a session is a date this booking could be moved to.
     *
     * The same rules the catalogue applies to anything bookable — on sale, not
     * private, not in the past — plus "the same course", because a transfer is
     * a change of date and not a change of purchase.
     */
    private function isTransferTarget(int $sessionId, int $courseId, int $fromSessionId): bool
    {
        if ($sessionId === $fromSessionId) {
            return false;
        }

        $row = db_connect()->table('course_sessions')->where('id', $sessionId)->get()->getRowArray();

        return $row !== null
            && (int) $row['course_id'] === $courseId
            && (int) $row['is_private'] === 0
            && in_array((string) $row['status'], CourseSessionModel::BOOKABLE, true)
            && (empty($row['start_date']) || $row['start_date'] >= date('Y-m-d'));
    }

    /**
     * The currency this enrolment was bought in.
     *
     * Read back from the order rather than resolved for the visitor, because a
     * transfer fee is charged against a purchase that already happened. Somebody
     * who bought in rupees and is reading the site in dollars today is still
     * owed a rupee figure, and a converted one would be a number nobody chose.
     */
    private function currencyFor(array $enrolment): string
    {
        if (! empty($enrolment['order_item_id'])) {
            $row = db_connect()->table('order_items oi')
                ->select('o.currency')
                ->join('orders o', 'o.id = oi.order_id')
                ->where('oi.id', (int) $enrolment['order_item_id'])
                ->get()->getRowArray();

            if (! empty($row['currency'])) {
                return strtoupper((string) $row['currency']);
            }
        }

        // A comped or imported enrolment has no order behind it. The visitor's
        // own currency is the least wrong fallback, and the transfer fee for it
        // is a setting somebody chose in that currency either way.
        return current_currency();
    }

    /**
     * The published transfer fee for a currency, in minor units, or null.
     *
     * Null is a real answer and the view says so: no fee has been published in
     * this currency, so the amount is confirmed when the request is reviewed.
     * A zero printed as though it were a decision would tell a learner the move
     * is free when nobody has said that.
     */
    private function transferFee(string $currency): ?int
    {
        $stored = setting('transfer_fee_' . strtolower($currency), null, 'policy');

        return $stored === null || $stored === '' ? null : (int) $stored;
    }

    /**
     * A stored URL, but only if it is one a browser should follow.
     *
     * The joining link is typed by an administrator and then encrypted, so it
     * is trusted input — but it is also the one value on this page that goes
     * straight into an `href`, and `javascript:` in an admin field is a
     * mistake that only shows up once, in a learner's browser. Anything that is
     * not http or https reads as "no link", which the view already handles.
     */
    private function safeUrl(string $url): string
    {
        $url = trim($url);

        return preg_match('~^https?://~i', $url) === 1 ? $url : '';
    }

    /** The learner's own timezone, if the profile carries a valid one. */
    private function learnerTimezone(): ?string
    {
        $user = LearnerAuth::record();
        $zone = trim((string) ($user['timezone'] ?? ''));

        return $zone !== '' && in_array($zone, \DateTimeZone::listIdentifiers(), true) ? $zone : null;
    }

    /**
     * Every timezone, grouped by region for the profile's select.
     *
     * Four hundred options in one flat list is a control nobody can use.
     * Optgroups give it structure a screen reader announces and a keyboard user
     * can skip through, and the region is the part of an identifier a person
     * actually recognises.
     *
     * @return array<string, array<string, string>> region => [identifier => city]
     */
    private function timezoneGroups(): array
    {
        $groups = [];
        foreach (\DateTimeZone::listIdentifiers() as $identifier) {
            $parts  = explode('/', $identifier, 2);
            $region = $parts[0];
            // UTC and the handful of other single-segment identifiers have no
            // city half; they are grouped under their own name rather than
            // dropped, because UTC is the one somebody deliberately picks.
            $city   = str_replace('_', ' ', $parts[1] ?? $identifier);

            $groups[$region][$identifier] = $city;
        }

        return $groups;
    }
}
