<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Auth\Database\Seeds\RoleSeeder;
use Modules\Commerce\Models\LeadModel;

/**
 * The corporate pipeline: everybody who asked the school for something and has
 * not yet been answered.
 *
 * Six different forms end in `training_leads` — a request for a quote, a
 * contact message, a newsletter sign-up, a resource download, a place on a
 * waitlist and "please run this on a date I can make" — and they are one table
 * on purpose, because they are one job. Somebody opens this screen in the
 * morning to find out who is waiting to hear back.
 *
 * Not a `BaseCrudController`, and there is no "New lead" button. A lead is not
 * authored: it is a record of somebody having asked. The only things this
 * screen writes are the three that belong to the school rather than to the
 * enquirer — where the conversation has got to, whose conversation it is, and
 * what was said.
 *
 * **Winning a lead here sells nothing.** `won` is a note about a conversation,
 * not a transaction: no order, no quote, no seat and no enrolment comes out of
 * this controller. A private cohort becomes a `course_sessions` row and an
 * invoice through Orders, after a human has agreed a price. A status that
 * quietly created an order would put a company in a classroom without anybody
 * having agreed what it costs.
 *
 * **The enquirer's own words are never edited.** `message`, `courses_json`,
 * `preferred_dates` and the rest are read-only on both screens. What the school
 * thinks goes in a note, and notes are appended with a timestamp rather than
 * written over the top of each other — see `update()`, which is where the one
 * real trap on this screen lives.
 */
class TrainingLeads extends BaseController
{
    /**
     * The forms that write to this table.
     *
     * Matches the migration's comment on `training_leads.type`. Used to build
     * the type filter and to reject a `?type=` that is not one of these, so a
     * hand-edited URL can only ever narrow the view.
     */
    public const TYPES = ['corporate', 'contact', 'newsletter', 'resource', 'waitlist', 'date_request'];

    /**
     * The pipeline, in the order a deal walks through it.
     *
     * `won` and `lost` are both endings and both worth recording: a lost lead
     * with a note saying why is the only evidence the school will ever have
     * about what it is being beaten on.
     */
    public const STATUSES = ['new', 'contacted', 'quoted', 'won', 'lost'];

    /**
     * Rows per page.
     *
     * Newsletter sign-ups arrive for ever and are never closed, so this list
     * has no natural ceiling — unlike the job applications screen it is modelled
     * on, where a vacancy eventually closes.
     */
    private const PER_PAGE = 50;

    /** The longest note this screen will take in one go. See `update()`. */
    private const MAX_NOTE = 2000;

    /**
     * The point at which a payload is refused rather than written.
     *
     * `payload_json` is a TEXT column, which tops out at 65,535 bytes on MySQL.
     * A JSON document cut off at that boundary is not a shorter document, it is
     * an unparseable one, and it would take the budget bounds and the course
     * list down with the notes. The margin is deliberate.
     */
    private const PAYLOAD_LIMIT = 60000;

    /**
     * The queue, filtered by what kind of enquiry it is and where it has got to.
     *
     * Also the CSV export, on `?export=csv`, rather than a route of its own:
     * the export must honour exactly the filter on screen, and a second
     * endpoint is a second place for the two definitions of "the current list"
     * to drift apart.
     */
    public function index()
    {
        helper(['norlanka', 'commerce']);

        $filters = [
            'type'   => (string) $this->request->getGet('type'),
            'status' => (string) $this->request->getGet('status'),
        ];
        if (! in_array($filters['type'], self::TYPES, true)) {
            $filters['type'] = '';
        }
        if (! in_array($filters['status'], self::STATUSES, true)) {
            $filters['status'] = '';
        }

        if ((string) $this->request->getGet('export') === 'csv') {
            return $this->export($filters);
        }

        // One group-by answers both rows of chips. Each axis is counted with
        // the *other* filter applied and its own ignored, so choosing
        // "corporate" renarrows the status tabs while the type chips carry on
        // showing what the types nobody has clicked are holding.
        $matrix       = $this->counts();
        $typeCounts   = [];
        $statusCounts = [];
        foreach (self::TYPES as $type) {
            $typeCounts[$type] = self::tally($matrix, $type, $filters['status']);
        }
        foreach (self::STATUSES as $status) {
            $statusCounts[$status] = self::tally($matrix, $filters['type'], $status);
        }

        $total = self::tally($matrix, $filters['type'], $filters['status']);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min(max(1, (int) $this->request->getGet('page')), $pages);

        $rows = $this->query()
            ->where($this->narrow($filters))
            // Newest first by id rather than by created_at: created_at is
            // nullable, where a null sorts differs between SQLite and MySQL,
            // and the same list would then come out in two different orders on
            // the two engines this site runs on.
            ->orderBy('training_leads.id', 'DESC')
            ->findAll(self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        return view('Modules\Admin\Views\leads\index', [
            'title'        => 'Quote requests',
            'active'       => 'training-leads',
            'rows'         => $rows,
            'filters'      => $filters,
            'types'        => self::TYPES,
            'statuses'     => self::STATUSES,
            'typeCounts'   => $typeCounts,
            'statusCounts' => $statusCounts,
            'allCount'     => self::tally($matrix, '', $filters['status']),
            'total'        => $total,
            'page'         => $page,
            'pages'        => $pages,
        ]);
    }

    /** One enquiry, everything it carried, and the two fields the school owns. */
    public function show($id)
    {
        helper(['norlanka', 'commerce', 'catalog']);

        $row = $this->query()->where('training_leads.id', (int) $id)->first();
        if ($row === null) {
            return redirect()->to(site_url('admin/training-leads'))->with('error', 'That enquiry no longer exists.');
        }

        return view('Modules\Admin\Views\leads\show', [
            'title'    => 'Enquiry from ' . (($row['company'] ?? '') ?: ($row['name'] ?? '') ?: $row['email']),
            'active'   => 'training-leads',
            'row'      => $row,
            'statuses' => self::STATUSES,
            'owners'   => $this->owners(),
        ]);
    }

    /**
     * Move the lead along, hand it to somebody, and add a note.
     *
     * The note is **appended** to `payload_json` under a `notes` key, with the
     * time and the person who wrote it, and nothing already in that document is
     * disturbed. Two reasons, and the second is the one that bites:
     *
     *   A note is a record of a conversation on a date. Overwriting the last
     *   one — which is what a single `notes` textarea does, and what the job
     *   applications screen deliberately accepts for a much shorter pipeline —
     *   loses the reason a lead went quiet in March by the time somebody asks
     *   in June.
     *
     *   `payload_json` is not the notes column. It already carries the budget
     *   bounds in minor units that the visitor was actually shown, the course
     *   they could not find in the list, the resource they downloaded and their
     *   marketing consent. Decoding it, replacing it with `['notes' => …]` and
     *   encoding it again would silently destroy all of that, and nothing would
     *   report an error.
     *
     * Nothing is written until every check has passed, so a note that is too
     * long does not leave a status change half applied.
     */
    public function update($id)
    {
        $id    = (int) $id;
        $leads = new LeadModel();
        $row   = $leads->find($id);

        if ($row === null) {
            return redirect()->to(site_url('admin/training-leads'))->with('error', 'That enquiry no longer exists.');
        }

        $status = (string) $this->request->getPost('status');
        if (! in_array($status, self::STATUSES, true)) {
            return redirect()->back()->withInput()->with('error', 'That is not a stage a lead can be at.');
        }

        // An owner is checked against the staff list rather than merely cast to
        // an integer. `assigned_to` has no foreign key, so an id typed into the
        // form would otherwise stick — and the id of a *learner* would put a
        // customer's name in the console's owner column.
        //
        // The owner the lead already has is allowed through even when they are
        // no longer on that list. Somebody leaving has their console role taken
        // away, and without this every lead they were working would have its
        // owner silently wiped the first time anybody else opened it and
        // pressed Save — which is how the question "who was handling Bright
        // Bank" stops having an answer.
        $assigned = (int) $this->request->getPost('assigned_to');
        $allowed  = array_column($this->owners(), 'id');
        if ((int) ($row['assigned_to'] ?? 0) > 0) {
            $allowed[] = (int) $row['assigned_to'];
        }
        if ($assigned > 0 && ! in_array($assigned, $allowed, true)) {
            return redirect()->back()->withInput()->with('error', 'That person is not somebody who can be given a lead.');
        }

        $note = trim((string) $this->request->getPost('note'));
        if (mb_strlen($note) > self::MAX_NOTE) {
            return redirect()->back()->withInput()->with(
                'error',
                'That note is longer than ' . number_format(self::MAX_NOTE) . ' characters. Nothing has been saved — shorten it and send it again.'
            );
        }

        $admin = session()->get('admin_user') ?? [];
        $data  = [
            'status'      => $status,
            'assigned_to' => $assigned > 0 ? $assigned : null,
        ];

        if ($note !== '') {
            $payload = json_decode((string) ($row['payload_json'] ?? ''), true);
            $payload = is_array($payload) ? $payload : [];

            // array_values, because a payload hand-edited into an object would
            // otherwise turn the list into a map the moment it is re-encoded.
            $notes   = isset($payload['notes']) && is_array($payload['notes']) ? array_values($payload['notes']) : [];
            $notes[] = [
                'at'   => date('Y-m-d H:i:s'),
                'by'   => (string) ($admin['email'] ?? 'unknown'),
                'text' => $note,
            ];

            $payload['notes'] = $notes;
            $encoded          = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if (strlen((string) $encoded) > self::PAYLOAD_LIMIT) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'This lead has as many notes as it can hold. Nothing has been saved. Summarise the history somewhere durable — a quote, or the account — rather than adding to it here.'
                );
            }

            $data['payload_json'] = $encoded;
        }

        $leads->update($id, $data);

        log_message('info', 'Lead {id} set to {status} by {who}', [
            'id'     => $id,
            'status' => $status,
            'who'    => (string) ($admin['email'] ?? 'unknown'),
        ]);

        return redirect()->to(site_url('admin/training-leads/' . $id))->with(
            'message',
            $note === '' ? 'Lead updated.' : 'Lead updated and the note added.'
        );
    }

    /**
     * The list query, shared by the index, the export and the detail screen so
     * that all three agree about what a lead is.
     *
     * Every column is table-qualified and the owner's columns are aliased.
     * `users` carries its own `id`, `email`, `status`, `created_at` and
     * `updated_at`, so an unqualified `where` or an unaliased `email` here is
     * either an ambiguous-column error or, worse, the wrong person's address on
     * every row.
     */
    private function query()
    {
        return (new LeadModel())
            ->select('training_leads.*')
            ->select('u.first_name AS owner_first, u.last_name AS owner_last, u.email AS owner_email')
            ->join('users u', 'u.id = training_leads.assigned_to', 'left');
    }

    /**
     * The current filter as a where-array.
     *
     * @param array{type:string, status:string} $filters
     * @return array<string, string>
     */
    private function narrow(array $filters): array
    {
        $where = [];
        if ($filters['type'] !== '') {
            $where['training_leads.type'] = $filters['type'];
        }
        if ($filters['status'] !== '') {
            $where['training_leads.status'] = $filters['status'];
        }

        return $where;
    }

    /**
     * Every lead counted once, by type and by stage.
     *
     * @return array<string, array<string, int>> type => status => count
     */
    private function counts(): array
    {
        $rows = db_connect()->table('training_leads')
            ->select('type, status, COUNT(*) AS n', false)
            ->groupBy(['type', 'status'])
            ->get()->getResultArray();

        $matrix = [];
        foreach ($rows as $row) {
            $matrix[(string) $row['type']][(string) $row['status']] = (int) $row['n'];
        }

        return $matrix;
    }

    /**
     * Add up the cells of the matrix that match, where an empty string is
     * "any".
     *
     * A type or a stage that is not on either list above can only get into the
     * table by hand. It is still counted in the totals, because a figure that
     * quietly omits rows is worse than one with no chip to click.
     *
     * @param array<string, array<string, int>> $matrix
     */
    private static function tally(array $matrix, string $type, string $status): int
    {
        $sum = 0;
        foreach ($matrix as $rowType => $byStatus) {
            if ($type !== '' && $rowType !== $type) {
                continue;
            }
            foreach ($byStatus as $rowStatus => $n) {
                if ($status !== '' && $rowStatus !== $status) {
                    continue;
                }
                $sum += $n;
            }
        }

        return $sum;
    }

    /**
     * The people a lead can be given to: everybody holding a console role.
     *
     * Drawn from `RoleSeeder::STAFF`, which is the same list the admin sign-in
     * checks, so a learner can never appear here however many roles are added
     * later. DISTINCT rather than GROUP BY because somebody with two staff
     * roles is one person and two joined rows, and a grouped select of
     * un-aggregated columns is rejected by MySQL under ONLY_FULL_GROUP_BY.
     *
     * @return list<array{id:int, first_name:?string, last_name:?string, email:string}>
     */
    private function owners(): array
    {
        static $owners = null;
        if ($owners !== null) {
            return $owners;
        }

        $rows = db_connect()->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.email', false)
            ->distinct()
            ->join('role_user ru', 'ru.user_id = u.id')
            ->join('roles r', 'r.id = ru.role_id')
            ->whereIn('r.slug', RoleSeeder::STAFF)
            ->where('u.deleted_at', null)
            ->orderBy('u.first_name', 'ASC')
            ->orderBy('u.email', 'ASC')
            ->get()->getResultArray();

        return $owners = array_map(static fn (array $r): array => [
            'id'         => (int) $r['id'],
            'first_name' => $r['first_name'],
            'last_name'  => $r['last_name'],
            'email'      => (string) $r['email'],
        ], $rows);
    }

    /**
     * The list on screen, as a spreadsheet — which is how the newsletter gets
     * into a mailing tool, and how a sales list gets worked offline.
     *
     * **What comes out of here is personal data.** Names, work email addresses,
     * telephone numbers and the company somebody works for, in a file with no
     * password on it. It leaves the console the moment it is downloaded and
     * nothing here can protect it after that: it should go straight into the
     * tool it was exported for and then be deleted, not left sitting in a
     * downloads folder or forwarded as an attachment. The console is behind a
     * staff sign-in; a copy of this file on a laptop is not.
     *
     * Two consequences of that are built in rather than left to discipline.
     * `Marketing consent` is a column, because the newsletter export is the one
     * people mail from and consent is what decides whether they may — it is
     * recorded per enquiry and must travel with the address. And the internal
     * notes are **not** exported: they are the school talking to itself about a
     * customer, and a spreadsheet is exactly how that ends up forwarded to the
     * customer.
     *
     * @param array{type:string, status:string} $filters
     */
    private function export(array $filters)
    {
        $rows = $this->query()
            ->where($this->narrow($filters))
            ->orderBy('training_leads.id', 'DESC')
            ->findAll();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, [
            'ID', 'Received', 'Type', 'Stage', 'Owner', 'Name', 'Email', 'Phone',
            'Company', 'Country', 'Team size', 'Budget band', 'Courses of interest',
            'Preferred dates', 'Mode', 'Location', 'Marketing consent', 'Message',
            'Page', 'UTM source', 'UTM medium', 'UTM campaign',
        ]);

        foreach ($rows as $r) {
            $payload = json_decode((string) ($r['payload_json'] ?? ''), true);
            $payload = is_array($payload) ? $payload : [];
            $utm     = json_decode((string) ($r['utm_json'] ?? ''), true);
            $utm     = is_array($utm) ? $utm : [];
            $courses = json_decode((string) ($r['courses_json'] ?? ''), true);
            $courses = is_array($courses) ? $courses : [];

            $owner = trim(((string) ($r['owner_first'] ?? '')) . ' ' . ((string) ($r['owner_last'] ?? '')));

            fputcsv($out, [
                $r['id'],
                $r['created_at'],
                $r['type'],
                $r['status'],
                $owner !== '' ? $owner : (string) ($r['owner_email'] ?? ''),
                $r['name'],
                $r['email'],
                $r['phone'],
                $r['company'],
                $r['country'],
                $r['team_size'],
                // The stored code, not the formatted band. A spreadsheet cell
                // holding "Rs 25,000 to Rs 75,000" cannot be sorted, filtered
                // or compared; the code can, and the console shows the money.
                $r['budget_band'],
                implode('; ', array_map(
                    static fn ($c): string => is_array($c) ? (string) ($c['title'] ?? $c['slug'] ?? '') : (string) $c,
                    $courses
                )),
                $r['preferred_dates'],
                $r['mode'],
                $r['location'],
                // Absent means never asked, which is not the same as refused —
                // a waitlist request carries no consent question at all. Said
                // in words rather than left as an empty cell somebody reads as
                // a no, or worse, as a yes.
                array_key_exists('marketing_opt_in', $payload)
                    ? (! empty($payload['marketing_opt_in']) ? 'yes' : 'no')
                    : 'not asked',
                $r['message'],
                $r['source'],
                $utm['utm_source'] ?? '',
                $utm['utm_medium'] ?? '',
                $utm['utm_campaign'] ?? '',
            ]);
        }

        rewind($out);
        $csv = (string) stream_get_contents($out);
        fclose($out);

        $name = 'leads-' . ($filters['type'] !== '' ? $filters['type'] . '-' : '')
            . ($filters['status'] !== '' ? $filters['status'] . '-' : '')
            . date('Ymd-His') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
            // Excel reads a CSV as the system's legacy encoding unless a byte
            // order mark says otherwise, which turns every accented name into
            // mojibake on a Windows machine.
            ->setBody("\xEF\xBB\xBF" . $csv);
    }
}
