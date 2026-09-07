<?php

namespace Modules\Learning\Services;

use CodeIgniter\Database\BaseConnection;
use Modules\Commerce\Models\OrderModel;
use Modules\Commerce\Services\InventoryService;

/**
 * Fulfilment: the moment a payment becomes a seat, a learner account, a
 * calendar invitation and a place in a classroom.
 *
 * This is the only place in the site that may mark an order paid, and it is
 * reached from exactly two directions: a verified gateway webhook, and an
 * administrator recording a bank transfer. Never from a browser redirect. A
 * redirect can be replayed, forged, or simply never arrive because the buyer
 * closed the tab on a train, and a checkout that concludes a sale on its own
 * is a checkout anybody can conclude by constructing a request.
 *
 * Everything that changes state happens in one transaction, guarded by a lock
 * on the order row and by `paid_at IS NULL`. Gateways redeliver webhooks —
 * sometimes for days, sometimes in a burst when their own queue drains — and
 * the second delivery must be a no-op, not a second set of enrolments, a second
 * invoice and a second round of confirmation emails.
 *
 * Email is sent *after* the transaction commits, never inside it. A mail server
 * that is slow holds a database write lock open; a mail server that fails would
 * roll back a payment that has genuinely been taken.
 */
class EnrolmentService
{
    /**
     * How long a self-paced enrolment lasts, in months.
     *
     * Named here rather than written into the date maths, because the same
     * number appears in the sentence a buyer reads on the course page
     * (`Catalog.ondemand.self_paced_note`). Two places is one too many, but a
     * constant at least makes the second one findable.
     */
    public const SELF_PACED_MONTHS = 12;

    private BaseConnection $db;
    private InventoryService $inventory;

    public function __construct(?BaseConnection $db = null, ?InventoryService $inventory = null)
    {
        $this->db        = $db ?? db_connect();
        $this->inventory = $inventory ?? new InventoryService($this->db);
    }

    /**
     * Fulfil a paid order, exactly once.
     *
     * @return array{ok:bool, reason:string, enrolments:list<int>, already:bool}
     */
    public function fulfil(int $orderId): array
    {
        $this->db->transStart();

        $order = $this->lockOrder($orderId);
        if ($order === null) {
            $this->db->transComplete();

            return ['ok' => false, 'reason' => 'missing', 'enrolments' => [], 'already' => false];
        }

        // The idempotency guard. Everything below this line runs once per order,
        // whatever a gateway decides to do with its retry queue.
        if (! empty($order['paid_at'])) {
            $this->db->transComplete();

            return ['ok' => true, 'reason' => 'already_paid', 'enrolments' => [], 'already' => true];
        }

        $items = $this->db->table('order_items')->where('order_id', $orderId)
            ->orderBy('id', 'ASC')->get()->getResultArray();

        // Seats first: if the inventory cannot be committed the order should not
        // be marked paid, and rolling back is the only honest outcome.
        $sessionItems = array_values(array_filter($items, static fn ($i) => ! empty($i['session_id'])));
        $cartId       = $this->cartIdFor($order);
        $touched      = $this->inventory->commit(
            array_map(static fn ($i) => ['session_id' => (int) $i['session_id'], 'qty' => (int) $i['qty']], $sessionItems),
            $cartId
        );

        $this->db->table('orders')->where('id', $orderId)->update([
            'status'  => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        $enrolmentIds = [];
        $notify       = [];

        foreach ($items as $item) {
            // One enrolment per seat, and a seat is one attendee. A four-seat
            // line is four people, four accounts and four sets of joining
            // instructions — not one enrolment with a quantity, which is the
            // shortcut that makes corporate bookings unusable.
            foreach ($this->attendeesFor($item, $order) as $attendee) {
                $userId = $this->findOrInviteUser($attendee, $order);

                $existing = $this->db->table('enrolments')
                    ->where('order_item_id', (int) $item['id'])->where('user_id', $userId)
                    ->get()->getRowArray();

                if ($existing !== null) {
                    $enrolmentIds[] = (int) $existing['id'];
                    continue;
                }

                $mode = json_decode((string) $item['meta_snapshot_json'], true)['mode'] ?? 'LIVE_ONLINE';

                $this->db->table('enrolments')->insert([
                    'user_id'       => $userId,
                    'order_item_id' => (int) $item['id'],
                    'course_id'     => (int) ($item['course_id'] ?? 0) ?: $this->courseIdForBundleItem($item),
                    'session_id'    => $item['session_id'] ? (int) $item['session_id'] : null,
                    'bundle_id'     => $item['bundle_id'] ? (int) $item['bundle_id'] : null,
                    'account_id'    => $order['account_id'] ?? null,
                    'mode'          => $mode,
                    'status'        => 'active',
                    'source'        => $order['account_id'] ? 'corporate' : 'purchase',
                    'enrolled_at'   => date('Y-m-d H:i:s'),
                    'expires_at'    => $this->expiryFor($mode),
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

                $id             = (int) $this->db->insertID();
                $enrolmentIds[] = $id;
                $notify[]       = ['enrolment_id' => $id, 'attendee' => $attendee, 'item' => $item];
            }
        }

        $this->issueInvoice($order);

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['ok' => false, 'reason' => 'db', 'enrolments' => [], 'already' => false];
        }

        // Outside the transaction, deliberately. A class reaching its minimum
        // is worth telling everybody about — "this class is confirmed to run"
        // removes the biggest hesitation in booking a dated course — but it is
        // not worth holding a lock for.
        foreach ($touched as $sessionId) {
            $this->inventory->refreshStatus($sessionId);
        }

        $this->sendConfirmations($order, $notify);

        return ['ok' => true, 'reason' => 'ok', 'enrolments' => $enrolmentIds, 'already' => false];
    }

    /**
     * Cancel a session and give everybody on it their money back or a free
     * move — the school-initiated case, when a class falls below its minimum.
     *
     * One action in the admin rather than a checklist, because the alternative
     * is an administrator emailing eleven people by hand on the morning they
     * were expecting to travel.
     *
     * @return array{cancelled:int, enrolments:list<int>}
     */
    public function cancelSession(int $sessionId, string $reason): array
    {
        $this->db->transStart();

        $this->db->table('course_sessions')->where('id', $sessionId)->update([
            'status'        => 'cancelled',
            'cancel_reason' => mb_substr($reason, 0, 255),
        ]);

        $enrolments = $this->db->table('enrolments')
            ->where('session_id', $sessionId)->where('status', 'active')
            ->get()->getResultArray();

        foreach ($enrolments as $enrolment) {
            $this->db->table('enrolments')->where('id', (int) $enrolment['id'])
                ->update(['status' => 'cancelled', 'updated_at' => date('Y-m-d H:i:s')]);
        }

        $this->db->transComplete();

        // The seats go back so the session's own numbers stay honest even after
        // it has been cancelled; a cancelled class showing eleven sold is a
        // report nobody can read.
        $this->inventory->reconcile($sessionId);

        return ['cancelled' => count($enrolments), 'enrolments' => array_map('intval', array_column($enrolments, 'id'))];
    }

    /**
     * Move a learner from one date to another, and settle the request.
     *
     * A learner could ask to be moved and nobody could act on it: the request
     * wrote a `reschedule_requests` row, `Dashboard::requestTransfer()`'s own
     * docblock said "an administrator decides, and the move happens there", and
     * no administrator screen read that table. Five separate strings told the
     * learner "we are looking at it" and "we will email you once it is settled"
     * while the row sat where nothing would ever see it.
     *
     * The move is an inventory operation, so it happens here rather than in a
     * controller: a seat is taken on the destination under its lock, and only
     * if that succeeds is the seat given back on the date being left. That
     * order matters. Releasing first would put the learner briefly on no
     * course at all, and if the destination turned out to be full it would
     * leave them on neither — the seat they paid for sold to somebody else
     * while they waited.
     *
     * The fee is not charged here. It was quoted when the request was made,
     * from the notice given on the date being left, and taking money is the
     * Orders screen's job — this records what was agreed and moves the seat.
     *
     * @return array{ok:bool, reason:string, left:int|null}
     */
    public function transfer(int $requestId, int $toSessionId, string $note = ''): array
    {
        $request = $this->db->table('reschedule_requests')->where('id', $requestId)->get()->getRowArray();

        if ($request === null || $request['status'] !== 'requested') {
            return ['ok' => false, 'reason' => 'not_open', 'left' => null];
        }

        $enrolment = $this->db->table('enrolments')
            ->where('id', (int) $request['enrolment_id'])->get()->getRowArray();

        if ($enrolment === null || $enrolment['status'] !== 'active') {
            return ['ok' => false, 'reason' => 'not_active', 'left' => null];
        }

        $fromSessionId = (int) ($enrolment['session_id'] ?? 0);

        if ($fromSessionId === $toSessionId) {
            return ['ok' => false, 'reason' => 'same_date', 'left' => null];
        }

        // The destination first, and outside the enrolment transaction: it takes
        // its own lock, and it is the step allowed to refuse.
        $taken = $this->inventory->sellDirect($toSessionId);
        if (! $taken['ok']) {
            return $taken;
        }

        $destination = $this->db->table('course_sessions')
            ->where('id', $toSessionId)->get()->getRowArray();

        $this->db->transStart();

        $this->db->table('enrolments')->where('id', (int) $enrolment['id'])->update([
            'session_id' => $toSessionId,
            'mode'       => (string) ($destination['mode'] ?? $enrolment['mode']),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->table('reschedule_requests')->where('id', $requestId)->update([
            'status'        => 'completed',
            'to_session_id' => $toSessionId,
            'decision_note' => mb_substr($note, 0, 255),
            'decided_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();

        // Only now is the old seat given back, and only if there was one: a
        // self-paced enrolment has no session to leave.
        if ($fromSessionId > 0) {
            $this->inventory->unsell($fromSessionId);
            $this->inventory->refreshStatus($fromSessionId);
        }
        $this->inventory->refreshStatus($toSessionId);

        $this->tellLearner((int) $enrolment['user_id'], 'moved', $request, $destination, $note);

        return ['ok' => true, 'reason' => 'moved', 'left' => $taken['left']];
    }

    /**
     * Refuse a transfer request, with a reason the learner will be shown.
     *
     * Nothing moves and no seat changes hands; the enrolment is exactly where
     * it was. Declining is recorded rather than deleted so the learner's page
     * can say what happened instead of silently losing their request.
     */
    public function declineTransfer(int $requestId, string $note): array
    {
        $request = $this->db->table('reschedule_requests')->where('id', $requestId)->get()->getRowArray();

        if ($request === null || $request['status'] !== 'requested') {
            return ['ok' => false, 'reason' => 'not_open'];
        }

        $this->db->table('reschedule_requests')->where('id', $requestId)->update([
            'status'        => 'declined',
            'decision_note' => mb_substr($note, 0, 255),
            'decided_at'    => date('Y-m-d H:i:s'),
        ]);

        $enrolment = $this->db->table('enrolments')
            ->where('id', (int) $request['enrolment_id'])->get()->getRowArray();

        if ($enrolment !== null) {
            $this->tellLearner((int) $enrolment['user_id'], 'declined', $request, null, $note);
        }

        return ['ok' => true, 'reason' => 'declined'];
    }

    // ── Internals ───────────────────────────────────────────────────────────

    private function lockOrder(int $orderId): ?array
    {
        // Same reasoning as InventoryService::lockSession: SQLite takes its
        // write lock when a write is issued, not when a transaction opens, so
        // a plain SELECT inside a transaction protects nothing there.
        if (stripos($this->db->DBDriver, 'sqlite') !== false) {
            $this->db->query('UPDATE orders SET updated_at = updated_at WHERE id = ?', [$orderId]);

            return $this->db->query('SELECT * FROM orders WHERE id = ?', [$orderId])->getRowArray();
        }

        return $this->db->query('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$orderId])->getRowArray();
    }

    /**
     * The attendees on one order line.
     *
     * The buyer is the fallback for any seat nobody named — somebody buying one
     * seat for themselves should not have to type their own name twice — but a
     * named attendee always wins, because on a corporate order the buyer is
     * usually not in the room.
     *
     * @return list<array{name:string, email:string}>
     */
    private function attendeesFor(array $item, array $order): array
    {
        $billing   = json_decode((string) $order['billing_json'], true) ?: [];
        $attendees = json_decode((string) $item['attendee_json'], true) ?: [];

        $out = [];
        foreach ($attendees as $attendee) {
            $email = trim((string) ($attendee['email'] ?? ''));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[] = ['name' => trim((string) ($attendee['name'] ?? '')) ?: $email, 'email' => $email];
            }
        }

        // Top up to the quantity bought with the buyer, so a line can never
        // produce fewer enrolments than seats paid for. A missing attendee is a
        // detail to chase, not a seat to forget.
        while (count($out) < (int) $item['qty']) {
            $out[] = [
                'name'  => trim((string) ($billing['name'] ?? '')) ?: (string) ($billing['email'] ?? ''),
                'email' => (string) ($billing['email'] ?? ''),
            ];
        }

        // Two seats named to the same person on one line is a typo, not two
        // enrolments; the unique key on (order_item_id, user_id) would reject
        // the second anyway, and catching it here keeps the count honest.
        $seen = [];

        return array_values(array_filter($out, static function (array $a) use (&$seen): bool {
            $key = strtolower($a['email']);
            if ($key === '' || isset($seen[$key])) {
                return false;
            }
            $seen[$key] = true;

            return true;
        }));
    }

    /**
     * Find the learner's account, or create an invited one.
     *
     * An attendee named by somebody else has never visited this site. They get
     * an account with no usable password and a set-password link in their
     * joining email — rather than no account at all, which would leave them
     * unable to reach their own recording, materials or certificate.
     */
    private function findOrInviteUser(array $attendee, array $order): int
    {
        $email = strtolower(trim($attendee['email']));

        $user = $this->db->table('users')->where('email', $email)->get()->getRowArray();
        if ($user !== null) {
            return (int) $user['id'];
        }

        $parts = preg_split('/\s+/', trim($attendee['name']), 2) ?: [];

        $this->db->table('users')->insert([
            'email'         => $email,
            // Not a hash of anything: a random 64-byte string can never be
            // produced by password_verify, so the account cannot be signed into
            // until its owner sets a password through the emailed link.
            'password_hash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            'first_name'    => mb_substr($parts[0] ?? '', 0, 64),
            'last_name'     => mb_substr($parts[1] ?? '', 0, 64),
            'country'       => $order['country'] ?? null,
            'status'        => 'invited',
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $userId = (int) $this->db->insertID();

        $role = $this->db->table('roles')->where('slug', 'learner')->get()->getRowArray();
        if ($role !== null) {
            $this->db->table('role_user')->insert(['user_id' => $userId, 'role_id' => (int) $role['id']]);
        }

        return $userId;
    }

    /**
     * A bundle line has no single course, so its enrolment is recorded against
     * the first course in the programme and the bundle id carries the rest.
     * The account area walks `bundle_items` from there.
     */
    /**
     * When this enrolment stops granting access, or null for never.
     *
     * The catalogue sells the self-paced library on a twelve-month licence —
     * "start the moment you buy it, work at your own pace, and keep access for
     * twelve months" — and that sentence is the reason a recording costs a
     * tenth of a taught seat. Until this existed nothing recorded the end of
     * the term, so the promise and the software disagreed, quietly and in the
     * buyer's favour, which is the kind of disagreement nobody reports.
     *
     * A taught seat gets no expiry. The class happens on a date, the
     * certificate is issued, and the learner should still be able to open the
     * record of it in three years; putting a clock on that would take away
     * something nobody sold them.
     *
     * The term is dated from fulfilment rather than from the first lesson: the
     * licence begins when the purchase completes, which is what the sentence
     * on the course page says and what the buyer can point at.
     */
    private function expiryFor(string $mode): ?string
    {
        if ($mode !== 'SELF_PACED') {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime('+' . self::SELF_PACED_MONTHS . ' months'));
    }

    private function courseIdForBundleItem(array $item): int
    {
        if (empty($item['bundle_id'])) {
            return 0;
        }

        $row = $this->db->table('bundle_items')->select('course_id')
            ->where('bundle_id', (int) $item['bundle_id'])
            ->orderBy('sort_order', 'ASC')->get()->getRowArray();

        return (int) ($row['course_id'] ?? 0);
    }

    private function cartIdFor(array $order): int
    {
        $row = $this->db->table('carts')->select('id')
            ->where('user_id', $order['user_id'])
            ->orderBy('id', 'DESC')->get()->getRowArray();

        return (int) ($row['id'] ?? 0);
    }

    /**
     * Issue the invoice, once, with its own gapless number series.
     *
     * Separate from the order number because accounting needs a sequence with
     * no holes in it and orders can be abandoned.
     */
    private function issueInvoice(array $order): void
    {
        $existing = $this->db->table('invoices')->where('order_id', (int) $order['id'])->get()->getRowArray();
        if ($existing !== null) {
            return;
        }

        $last = $this->db->table('invoices')->selectMax('id')->get()->getRowArray();
        $next = (int) ($last['id'] ?? 0) + 1;

        $this->db->table('invoices')->insert([
            'order_id'   => (int) $order['id'],
            'number'     => sprintf('INV-%s-%05d', date('Y'), $next),
            'issued_at'  => date('Y-m-d H:i:s'),
            'due_at'     => $order['due_at'] ?? null,
            'status'     => 'paid',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Confirmation, joining instructions and a calendar file, to each attendee.
     *
     * Failures are logged and swallowed. The payment has been taken and the
     * seat is theirs; an SMTP timeout must not turn a completed purchase into
     * an error page, and the admin can resend.
     *
     * @param list<array{enrolment_id:int, attendee:array, item:array}> $notify
     */
    /**
     * Tell the learner what was decided about their transfer.
     *
     * The account page told them "we will email you once it is settled" before
     * anything could settle it and before anything could send that email. Now
     * that an administrator can decide, the sentence has to become true.
     *
     * Sent after the write and outside the transaction, for the reason given at
     * the top of this class: a slow mail server must not hold a database lock,
     * and a failed one must not roll back a move that has already happened. A
     * send that fails is logged and the decision stands — the account page
     * shows the outcome either way, which is what makes the email a
     * convenience rather than the only record.
     */
    private function tellLearner(int $userId, string $outcome, array $request, ?array $destination, string $note): void
    {
        try {
            $user = $this->db->table('users')->select('email, first_name')
                ->where('id', $userId)->get()->getRowArray();

            if ($user === null || trim((string) $user['email']) === '') {
                return;
            }

            $school = (string) setting('site_name', '');
            $when   = $destination && ! empty($destination['start_date'])
                ? date('j F Y', strtotime((string) $destination['start_date']))
                : null;

            $subject = $outcome === 'moved'
                ? 'Your booking has been moved'
                : 'About your request to move a booking';

            $lines = [];
            if ($outcome === 'moved') {
                $lines[] = $when === null
                    ? 'Your booking has been moved as you asked.'
                    : 'Your booking has been moved to ' . $when . '.';
                $lines[] = 'Your place is confirmed on the new date. Nothing else needs doing.';
            } else {
                $lines[] = 'We are not able to move this booking.';
                $lines[] = 'Your original place is unchanged and still yours.';
            }

            if (trim($note) !== '') {
                $lines[] = $note;
            }

            $body = '';
            foreach ($lines as $line) {
                $body .= '<p style="margin:0 0 16px;">' . esc($line) . '</p>';
            }

            $result = \Modules\Core\Libraries\Mailer::send(
                (string) $user['email'],
                $subject,
                view('Modules\Learning\Views\emails\_layout', [
                    'title'  => $subject,
                    'body'   => $body,
                    'school' => $school,
                ], ['saveData' => false])
            );

            if (! ($result['sent'] ?? false)) {
                log_message('error', 'Transfer decision email not sent for request {id}: {reason}', [
                    'id'     => (int) $request['id'],
                    'reason' => $result['error'] ?? '',
                ]);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Transfer decision email failed for request {id}: {msg}', [
                'id'  => (int) $request['id'],
                'msg' => $e->getMessage(),
            ]);
        }
    }

    private function sendConfirmations(array $order, array $notify): void
    {
        if ($notify === []) {
            return;
        }

        try {
            \Modules\Learning\Libraries\EnrolmentMail::send($order, $notify);
        } catch (\Throwable $e) {
            log_message('error', 'Enrolment confirmation failed for order {id}: {msg}', [
                'id'  => $order['id'],
                'msg' => $e->getMessage(),
            ]);
        }
    }
}
