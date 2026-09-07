<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Learning\Models\RescheduleRequestModel;
use Modules\Learning\Services\EnrolmentService;

/**
 * Requests to move a booking to another date.
 *
 * This screen exists because its absence was a lie. A learner could open their
 * account, ask to move a class, and be told "we are looking at it" and "we will
 * email you once it is settled" — and the row went into `reschedule_requests`,
 * which nothing in the console read. `Dashboard::requestTransfer()` even says
 * in its own docblock that "an administrator decides, and the move happens
 * there". There was no there.
 *
 * The queue is deliberately small. Approving needs one decision — which date —
 * and everything else about the move is the service's business: the seat on the
 * destination is taken under its lock before the old one is given back, so a
 * learner is never briefly on neither course, and a destination that filled up
 * while the request sat here refuses rather than overselling.
 *
 * **No money changes hands here.** The fee was quoted to the learner when they
 * asked, from the notice on the date they are leaving, and it is recorded on
 * the request. Taking it is the Orders screen's job. A fee box on this page
 * would be a second place that decides what a transfer costs, and the policy
 * page is already the first.
 */
class Transfers extends BaseController
{
    private const PER_PAGE = 50;

    public function index()
    {
        $db = db_connect();

        $status = (string) ($this->request->getGet('status') ?? 'requested');
        if (! in_array($status, ['requested', 'completed', 'declined', 'all'], true)) {
            $status = 'requested';
        }

        $page = max(1, (int) $this->request->getGet('page'));

        $rows = $this->queue($status, $page);

        // Counted per status so the tabs can carry numbers — an empty queue
        // should say so, and a queue with eleven waiting should say that too.
        $counts = [];
        foreach (['requested', 'completed', 'declined'] as $s) {
            $counts[$s] = $db->table('reschedule_requests')->where('status', $s)->countAllResults();
        }

        return view('Modules\Admin\Views\transfers\index', [
            'rows'       => $rows,
            'status'     => $status,
            'counts'     => $counts,
            'page'       => $page,
            'perPage'    => self::PER_PAGE,
            'freeNotice' => RescheduleRequestModel::FREE_NOTICE_BUSINESS_DAYS,
            'title'      => 'Transfer requests',
        ]);
    }

    public function approve($id)
    {
        $id = (int) $id;
        $to = (int) $this->request->getPost('to_session_id');

        if ($to <= 0) {
            return redirect()->back()->with('error', 'Choose the date this booking is moving to.');
        }

        $result = (new EnrolmentService())->transfer(
            $id,
            $to,
            trim((string) $this->request->getPost('note'))
        );

        if (! $result['ok']) {
            return redirect()->back()->with('error', self::refusal($result['reason']));
        }

        $admin = session()->get('admin_user') ?? [];
        log_message('notice', 'Transfer request {id} approved to session {to} by {who}.', [
            'id'  => $id,
            'to'  => $to,
            'who' => (string) ($admin['email'] ?? 'unknown'),
        ]);

        return redirect()->back()->with('message', 'Moved. The learner now holds a seat on the new date'
            . ($result['left'] === null ? '.' : ', which has ' . $result['left'] . ' left.'));
    }

    public function decline($id)
    {
        $id   = (int) $id;
        $note = trim((string) $this->request->getPost('note'));

        // Required, and checked here rather than with a `required` attribute:
        // this reason is shown to the learner, and "declined, no reason given"
        // is the message that generates the email nobody wants to answer.
        if ($note === '') {
            return redirect()->back()->with('error', 'Say why the transfer is being refused — the learner is shown this.');
        }

        $result = (new EnrolmentService())->declineTransfer($id, $note);

        if (! $result['ok']) {
            return redirect()->back()->with('error', self::refusal($result['reason']));
        }

        return redirect()->back()->with('message', 'Request declined. The learner keeps their original seat.');
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * The queue, with everything the decision needs on one row: who, which
     * course, the date they are leaving, the date they asked for, the notice
     * they gave and the fee that was quoted.
     *
     * @return list<array>
     */
    private function queue(string $status, int $page): array
    {
        $builder = db_connect()->table('reschedule_requests rr')
            ->select('rr.*, e.user_id, e.course_id, e.status AS enrolment_status,
                      u.first_name, u.last_name, u.email,
                      c.title AS course_title, c.slug AS course_slug,
                      fs.start_date AS from_date, fs.mode AS from_mode, fs.status AS from_status,
                      ts.start_date AS to_date, ts.mode AS to_mode', false)
            ->join('enrolments e', 'e.id = rr.enrolment_id')
            ->join('users u', 'u.id = e.user_id', 'left')
            ->join('courses c', 'c.id = e.course_id', 'left')
            ->join('course_sessions fs', 'fs.id = rr.from_session_id', 'left')
            ->join('course_sessions ts', 'ts.id = rr.to_session_id', 'left');

        if ($status !== 'all') {
            $builder->where('rr.status', $status);
        }

        $rows = $builder
            ->orderBy('rr.requested_at', 'DESC')
            ->orderBy('rr.id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get()->getResultArray();

        // The dates this booking could move to, per row: the same course, still
        // bookable, not the one being left. Asked per row because the queue is
        // short and each row is a different course; if it ever is not, this is
        // the loop to fold into one grouped query.
        foreach ($rows as &$row) {
            $row['alternatives'] = $row['status'] === 'requested'
                ? $this->alternativesFor((int) $row['course_id'], (int) $row['from_session_id'])
                : [];
        }

        return $rows;
    }

    /** @return list<array> */
    private function alternativesFor(int $courseId, int $exceptSessionId): array
    {
        return db_connect()->table('course_sessions')
            ->select('id, start_date, end_date, mode, seats_total, seats_sold, status')
            ->where('course_id', $courseId)
            ->where('id !=', $exceptSessionId)
            ->where('is_private', 0)
            ->whereIn('status', ['open', 'confirmed', 'waitlist'])
            ->groupStart()
                ->where('start_date IS NULL')
                ->orWhere('start_date >=', date('Y-m-d'))
            ->groupEnd()
            ->orderBy('start_date IS NULL', 'ASC', false)
            ->orderBy('start_date', 'ASC')
            ->get()->getResultArray();
    }

    private static function refusal(string $reason): string
    {
        return match ($reason) {
            'not_open'   => 'That request has already been settled.',
            'not_active' => 'That booking is no longer active, so there is nothing to move.',
            'same_date'  => 'That is the date they are already on.',
            'missing'    => 'That date no longer exists.',
            'closed'     => 'That date is not open for booking.',
            'gone'       => 'That date filled up while this request was waiting. Choose another.',
            'short'      => 'That date does not have a seat free. Choose another.',
            default      => 'The transfer could not be made.',
        };
    }
}
