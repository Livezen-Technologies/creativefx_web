<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Catalog\Models\CourseModel;
use Modules\Learning\Models\CertificateModel;
use Modules\Learning\Models\EnrolmentModel;
use Modules\Learning\Services\CertificateService;

/**
 * Enrolments — who holds a place, on what, and what that place was worth.
 *
 * Not a `BaseCrudController`, and there is no "New enrolment" button, because
 * an enrolment is not authored. It is the consequence of a payment, and
 * `EnrolmentService::fulfil()` is the only code allowed to make one. A create
 * form here would be a way to put a name in a classroom without a seat ever
 * having been sold — the chair would still be free as far as the inventory is
 * concerned, and the thirteenth person would arrive at a twelve-chair room.
 *
 * Two things may be changed from this screen and nothing else: the status of a
 * place somebody already holds, and whether a certificate has been issued for
 * it.
 *
 * **`source` is why this screen is worth building.** A free retake within six
 * months is an enrolment at zero price; so is a complimentary place, and so is
 * a row imported from a spreadsheet at launch. A head count that cannot tell
 * those from a sale reports a business that is growing when it is not. So the
 * breakdown along the top is by source rather than by status, the source is on
 * every row, and the price of the order line the enrolment came from sits next
 * to it — a blank there is the honest answer that no money was taken.
 *
 * **Cancelling here releases no seat and refunds no money.** Seats come back
 * through `InventoryService` when an order is refunded, and that happens on the
 * Orders screen where the money is. A status change that quietly returned
 * inventory would let one mis-click resell a chair that is still occupied, and
 * a status change that quietly refunded would be worse. The view says as much
 * above the table, because an administrator who believes this button refunds
 * will not go and refund.
 *
 * Certificate eligibility is not restated here. It is a rule in
 * `CertificateService` — attendance for a taught class, the final quiz for
 * self-paced study — and this screen's job is to surface the refusal in words
 * and to offer the override as a separate, named action rather than as the
 * button somebody was already reaching for.
 */
class Enrolments extends BaseController
{
    /**
     * Every status an enrolment can hold — the filter's vocabulary.
     *
     * Matches the migration's comment on `enrolments.status`. A `?status=` that
     * is not one of these is dropped rather than passed to a builder, so a
     * hand-edited URL can only ever narrow the view.
     */
    public const STATUSES = ['active', 'completed', 'cancelled', 'no_show', 'transferred'];

    /**
     * The statuses an administrator may move an enrolment to.
     *
     * `transferred` is missing on purpose: it is what a reschedule leaves
     * behind, and a row that says "transferred" without a new enrolment at the
     * other end is a learner who has been moved to nowhere.
     */
    private const EDITABLE_STATUSES = ['active', 'completed', 'cancelled', 'no_show'];

    /** Matches the migration's comment on `enrolments.source`. */
    public const SOURCES = ['purchase', 'corporate', 'retake', 'comp', 'import'];

    /**
     * Sources that never carried money.
     *
     * Named rather than inferred from a zero price: an order line can legit-
     * imately be zero for a fully-discounted sale, and a retake that was
     * mistakenly attached to an order line is still not revenue.
     */
    public const UNPAID_SOURCES = ['retake', 'comp', 'import'];

    private const PER_PAGE = 50;

    public function index()
    {
        helper(['norlanka', 'catalog', 'commerce']);
        $db = db_connect();

        $filters = [
            'course'  => (int) $this->request->getGet('course'),
            'session' => (int) $this->request->getGet('session'),
            'status'  => (string) $this->request->getGet('status'),
            'source'  => (string) $this->request->getGet('source'),
        ];
        if (! in_array($filters['status'], self::STATUSES, true)) {
            $filters['status'] = '';
        }
        if (! in_array($filters['source'], self::SOURCES, true)) {
            $filters['source'] = '';
        }

        // Applied to two different builders below — the model's, which lists,
        // and the connection's, which counts by source — so it is written once.
        // Source is deliberately not in here: the chips have to keep showing
        // what the other sources hold while one of them is selected.
        $narrow = static function ($builder) use ($filters) {
            if ($filters['course'] > 0) {
                $builder->where('enrolments.course_id', $filters['course']);
            }
            if ($filters['session'] > 0) {
                $builder->where('enrolments.session_id', $filters['session']);
            }
            if ($filters['status'] !== '') {
                $builder->where('enrolments.status', $filters['status']);
            }

            return $builder;
        };

        $counts = $this->countsBySource($narrow($db->table('enrolments')));

        // The row count comes out of the breakdown rather than from a second
        // COUNT: with no source chosen the list is every source added up, and
        // with one chosen it is exactly that chip's figure.
        $total = $filters['source'] === '' ? array_sum($counts) : ($counts[$filters['source']] ?? 0);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min(max(1, (int) $this->request->getGet('page')), $pages);

        // Every join below is one row or none — the learner, the course, the
        // date, the certificate, the order line it was sold on. That is what
        // lets the count above be taken from the breakdown instead of from a
        // second query: a join that fanned out would list more rows than it
        // counted, and the last page would never be reachable.
        $query = (new EnrolmentModel())
            ->select('enrolments.*, u.first_name, u.last_name, u.email, u.company')
            ->select('c.title AS course_title, c.slug AS course_slug')
            ->select('cs.start_date, cs.end_date, cs.status AS session_status, cs.mode AS session_mode')
            ->select('cert.serial AS certificate_serial, cert.verify_code AS certificate_code, cert.issued_at AS certificate_issued_at, cert.revoked_at AS certificate_revoked_at, cert.revoke_reason AS certificate_revoke_reason')
            ->select('oi.unit_price_cents, oi.qty, o.id AS order_id, o.order_no, o.currency')
            ->join('users u', 'u.id = enrolments.user_id', 'left')
            ->join('courses c', 'c.id = enrolments.course_id', 'left')
            ->join('course_sessions cs', 'cs.id = enrolments.session_id', 'left')
            ->join('certificates cert', 'cert.enrolment_id = enrolments.id', 'left')
            ->join('order_items oi', 'oi.id = enrolments.order_item_id', 'left')
            ->join('orders o', 'o.id = oi.order_id', 'left');

        $narrow($query);
        if ($filters['source'] !== '') {
            $query->where('enrolments.source', $filters['source']);
        }

        // Newest first by id, not by enrolled_at: enrolled_at is nullable for
        // an imported row, and where a null sorts differs between SQLite and
        // MySQL, so the same list would come out in two different orders.
        $rows = $query->orderBy('enrolments.id', 'DESC')
            ->findAll(self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        return view('Modules\Admin\Views\enrolments\index', [
            'title'         => 'Enrolments',
            'active'        => 'enrolments',
            'rows'          => $rows,
            'filters'       => $filters,
            'counts'        => $counts,
            'courses'       => $this->courseOptions(),
            'sessions'      => $this->sessionOptions($filters['course']),
            'statuses'      => self::STATUSES,
            'editable'      => self::EDITABLE_STATUSES,
            'sources'       => self::SOURCES,
            'unpaidSources' => self::UNPAID_SOURCES,
            'page'          => $page,
            'pages'         => $pages,
            'total'         => $total,
        ]);
    }

    /**
     * Move somebody between active, completed, cancelled and no_show.
     *
     * The seat, the order and the money are all untouched — see the class
     * comment. What this does change is `completed_at`, because that date is
     * what the account area and the reports read to mean "finished", and a row
     * moved back out of `completed` while keeping the date would count as both.
     */
    public function update($id)
    {
        $id         = (int) $id;
        $enrolments = new EnrolmentModel();
        $row        = $enrolments->find($id);

        if ($row === null) {
            return redirect()->to(site_url('admin/enrolments'))->with('error', 'That enrolment no longer exists.');
        }

        $status = (string) $this->request->getPost('status');
        if (! in_array($status, self::EDITABLE_STATUSES, true)) {
            return redirect()->back()->with('error', 'That is not a status an enrolment can be moved to.');
        }

        if ($status === (string) $row['status']) {
            return redirect()->back()->with('message', 'Nothing changed — that place was already ' . self::statusLabel($status) . '.');
        }

        $enrolments->update($id, [
            'status' => $status,
            // Kept rather than restamped when it is already set, so re-saving a
            // completed enrolment does not move the date it was finished on.
            'completed_at' => $status === 'completed'
                ? ($row['completed_at'] ?: date('Y-m-d H:i:s'))
                : null,
        ]);

        $admin = session()->get('admin_user') ?? [];
        log_message('info', 'Enrolment {id} moved from {from} to {to} by {who}', [
            'id'   => $id,
            'from' => (string) $row['status'],
            'to'   => $status,
            'who'  => (string) ($admin['email'] ?? 'unknown'),
        ]);

        return redirect()->back()->with('message', 'Marked ' . self::statusLabel($status) . '.');
    }

    /**
     * Issue the certificate, or say in words why it cannot be.
     *
     * `force` is posted by its own button, in its own form, with its own note
     * field — never as a checkbox beside the ordinary one. It exists for the
     * case a school genuinely has, which is that somebody attended and the
     * register was not marked, and it is recorded with who overrode the rule
     * and why. An override that is one click away from the normal path is an
     * override nobody notices has become the normal path.
     */
    public function issueCertificate($id)
    {
        $id  = (int) $id;
        $row = (new EnrolmentModel())->find($id);

        if ($row === null) {
            return redirect()->to(site_url('admin/enrolments'))->with('error', 'That enrolment no longer exists.');
        }

        // Guarding this screen's own action, not restating the service's rule.
        // A cancelled place is somebody who is not on the course, and a
        // transferred one has moved to another date whose enrolment is where
        // the certificate belongs; eligible() knows about attendance and
        // quizzes and has no opinion on either of those.
        if (in_array((string) $row['status'], ['cancelled', 'transferred'], true)) {
            return redirect()->back()->with('error', 'This place is '
                . self::statusLabel((string) $row['status'])
                . ', so no certificate can be issued against it.');
        }

        $force  = (bool) $this->request->getPost('force');
        $reason = trim((string) $this->request->getPost('reason'));

        // Server-side rather than a `required` attribute: the override lives
        // inside a collapsed <details>, and a required control a browser cannot
        // scroll to blocks the whole form with a message nobody can see.
        if ($force && $reason === '') {
            return redirect()->back()->with('error', 'Say why the rule is being overridden before issuing this certificate.');
        }

        $service = new CertificateService();
        $check   = $service->eligible($id);

        if ($check['reason'] === 'already') {
            // Read the existing row rather than calling issue() for it. issue()
            // would hand it back harmlessly, but a method named "issue" called
            // to answer "what was issued" is a line somebody later edits into a
            // second certificate.
            $existing = (new CertificateModel())->forEnrolment($id);

            return redirect()->back()->with('error', 'A certificate was already issued for this place'
                . ($existing === null ? '.' : ' (' . $existing['serial'] . ').'));
        }

        if (! $check['ok'] && ! $force) {
            return redirect()->back()->with('error', self::refusal($check['reason']));
        }

        $certificate = $service->issue($id, $force);

        if ($certificate === null) {
            // eligible() passed, so this is the learner or the course having
            // gone — a broken row rather than an unmet rule, and worth saying
            // differently so nobody spends the afternoon marking a register.
            return redirect()->back()->with('error', 'The certificate could not be issued: this enrolment has no learner or no course behind it. Tell a developer.');
        }

        $admin = session()->get('admin_user') ?? [];

        if ($force) {
            log_message('notice', 'Certificate {serial} issued for enrolment {id} by {who} against the eligibility rule ({rule}): {reason}', [
                'serial' => (string) $certificate['serial'],
                'id'     => $id,
                'who'    => (string) ($admin['email'] ?? 'unknown'),
                'rule'   => (string) $check['reason'],
                'reason' => mb_substr($reason, 0, 255),
            ]);
        }

        return redirect()->back()->with('message', 'Certificate ' . $certificate['serial'] . ' issued'
            . ($force ? ', overriding the eligibility rule. The override is in the log.' : '.'));
    }

    /**
     * Withdraw a certificate.
     *
     * `CertificateService::revoke()` has existed since the module was written
     * with nothing routed to it, so a certificate issued in error — the wrong
     * enrolment, an override that should not have been made, a place refunded
     * afterwards — could be created and never withdrawn. For a school whose
     * certificates carry a public verification page, that is the half of the
     * lifecycle that matters most: the page keeps saying the credential is good.
     *
     * The certificate is not deleted. `/verify/{code}` must keep answering, and
     * "this certificate was withdrawn on 3 March" is a true answer that a
     * missing row cannot give — a 404 there reads as a broken link, not as a
     * revocation, to exactly the employer who is checking.
     *
     * A reason is required. A revocation nobody wrote a reason for is one
     * nobody can explain to the learner who asks.
     */
    public function revokeCertificate($id)
    {
        $id     = (int) $id;
        $reason = trim((string) $this->request->getPost('reason'));

        $certificate = (new CertificateModel())->forEnrolment($id);

        if ($certificate === null) {
            return redirect()->back()->with('error', 'There is no certificate against this place to withdraw.');
        }

        if (! empty($certificate['revoked_at'])) {
            return redirect()->back()->with('error', 'Certificate ' . $certificate['serial']
                . ' was already withdrawn on ' . date('j M Y', strtotime((string) $certificate['revoked_at'])) . '.');
        }

        if ($reason === '') {
            return redirect()->back()->with('error', 'Say why this certificate is being withdrawn.');
        }

        (new CertificateService())->revoke((int) $certificate['id'], $reason);

        $admin = session()->get('admin_user') ?? [];
        log_message('notice', 'Certificate {serial} withdrawn by {who}: {reason}', [
            'serial' => (string) $certificate['serial'],
            'who'    => (string) ($admin['email'] ?? 'unknown'),
            'reason' => mb_substr($reason, 0, 255),
        ]);

        return redirect()->back()->with('message', 'Certificate ' . $certificate['serial']
            . ' withdrawn. Its verification page now says so.');
    }

    /**
     * The certificate PDF, for an administrator.
     *
     * The learner can already download their own from their account; nobody in
     * the office could, which made "send me a copy" a request only a developer
     * could answer.
     *
     * Rendered from the row if the stored file has gone — the PDF is a
     * rendering of the record, not the record itself.
     */
    public function downloadCertificate($id)
    {
        $id          = (int) $id;
        $certificate = (new CertificateModel())->forEnrolment($id);

        if ($certificate === null) {
            return redirect()->back()->with('error', 'There is no certificate against this place.');
        }

        try {
            $pdf = (new CertificateService())->pdf($certificate);
        } catch (\Throwable $e) {
            log_message('error', 'Certificate {serial} could not be rendered: {msg}', [
                'serial' => (string) $certificate['serial'],
                'msg'    => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'That certificate could not be rendered. Tell a developer.');
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $certificate['serial'] . '.pdf"')
            ->setBody($pdf);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * How many enrolments each source accounts for, under the current filters.
     *
     * @return array<string, int>
     */
    private function countsBySource($builder): array
    {
        $counts = array_fill_keys(self::SOURCES, 0);

        $rows = $builder->select('enrolments.source AS source, COUNT(*) AS n', false)
            ->groupBy('enrolments.source')
            ->get()->getResultArray();

        foreach ($rows as $row) {
            // A source this build does not list — an older row, somebody's
            // two-in-the-morning UPDATE — is counted rather than dropped, or
            // the chips stop adding up to the table underneath them.
            $source          = (string) $row['source'];
            $counts[$source] = ($counts[$source] ?? 0) + (int) $row['n'];
        }

        return $counts;
    }

    /** @return list<array> */
    private function courseOptions(): array
    {
        $rows = (new CourseModel())->select('id, title')->findAll();
        usort($rows, static fn (array $a, array $b): int => strcasecmp(t_field($a['title']), t_field($b['title'])));

        return $rows;
    }

    /**
     * The dates worth offering in the second dropdown.
     *
     * Every date the school has ever run is over two hundred rows, and a
     * dropdown of two hundred rows is a haystack rather than a filter. So:
     * the chosen course's own dates when a course is chosen, and otherwise only
     * the dates that actually have somebody on them.
     *
     * @return list<array>
     */
    private function sessionOptions(int $courseId): array
    {
        $db = db_connect();

        $query = $db->table('course_sessions cs')
            ->select('cs.id, cs.start_date, cs.end_date, cs.mode, c.title AS course_title')
            ->join('courses c', 'c.id = cs.course_id', 'left');

        if ($courseId > 0) {
            $query->where('cs.course_id', $courseId);
        } else {
            // Two steps rather than a sub-select, so the SQL is the same on
            // both drivers and an empty table costs one query instead of a join
            // over everything.
            $ids = array_map(
                static fn (array $r): int => (int) $r['session_id'],
                $db->table('enrolments')->distinct()->select('session_id')->where('session_id IS NOT NULL')->get()->getResultArray()
            );

            if ($ids === []) {
                return [];
            }

            $query->whereIn('cs.id', $ids);
        }

        return $query->orderBy('cs.start_date IS NULL', 'ASC', false)
            ->orderBy('cs.start_date', 'DESC')
            ->get(200)->getResultArray();
    }

    /** Plain wording for a status, for the message after a save. */
    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'active'      => 'active',
            'completed'   => 'completed',
            'cancelled'   => 'cancelled',
            'no_show'     => 'a no-show',
            'transferred' => 'transferred',
            default       => $status,
        };
    }

    /**
     * The eligibility refusal, in words, with the fix attached.
     *
     * The reason codes come from `CertificateService::eligible()`. Each of
     * these says what to go and do, because "not eligible" on its own sends an
     * administrator to the override button, which is the one place they should
     * arrive at last rather than first.
     */
    private static function refusal(string $reason): string
    {
        return match ($reason) {
            'attendance' => 'The register does not show enough attendance for a certificate. If they were there and the register was not marked, mark it on the date screen — that is the fix, not the override.',
            'quiz'       => 'The final quiz has not been passed yet, so this self-paced course is not finished.',
            default      => 'This course has not finished yet — the class is still to come, or the self-paced lessons are not all complete.',
        };
    }
}
