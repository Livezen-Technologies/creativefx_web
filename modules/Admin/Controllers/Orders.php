<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Commerce\Models\OrderModel;
use Modules\Commerce\Models\PaymentModel;
use Modules\Commerce\Services\InventoryService;
use Modules\Learning\Models\EnrolmentModel;
use Modules\Learning\Services\EnrolmentService;
use Throwable;

/**
 * Orders: the ledger, and the two actions that are allowed to move money.
 *
 * Everything else in this back office is a CRUD screen over something an editor
 * authors. An order is not authored — it is a record of something that already
 * happened — which is why this is written by hand rather than declared as a
 * `BaseCrudController`. A generic editor would put a text box over `status`,
 * and a text box over `status` is a way to enrol eleven people by typing the
 * word "paid".
 *
 * So nothing here writes a seat count, and nothing here concludes a sale:
 *
 *   - `markPaid()` records a `payments` row and hands the order to
 *     `EnrolmentService::fulfil()`, which is the only code in the application
 *     that may set `orders.paid_at`, create enrolments and issue the invoice.
 *   - `refund()` records a `refunds` row and, when the whole order goes back,
 *     returns the seats through `InventoryService::unsell()`.
 *
 * **Why markPaid exists at all.** The blueprint's rule is that payment is
 * webhook-authoritative, and the browser returning from a gateway proves
 * nothing. Bank transfer has no webhook and never will: a finance department
 * pays against an invoice and no API tells us. A person who can see the money
 * on a statement is therefore the second — and only other — way an enrolment
 * comes into existence, and it is guarded exactly as the webhook handler is.
 * The bank's own reference is the idempotency key, `PaymentModel::recordOnce()`
 * turns a second click into a no-op, and `orders.paid_at` behind it turns a
 * second fulfilment into one too.
 *
 * **Why refund moves no money.** It cannot. There are no merchant keys on this
 * install, and even with them, refunding a card from a back-office button is
 * the wrong shape: the gateway's own dashboard is where a refund is authorised
 * and where it is audited. This screen records what was done there so the
 * order, the seats and the enrolments agree with the bank. The form says so in
 * as many words, because an administrator who believes this button refunds the
 * card will not go and refund the card.
 */
class Orders extends BaseController
{
    /**
     * The order lifecycle, in the order a sale walks through it.
     *
     * Matches the migration's comment on `orders.status`. Used both to build
     * the filter and to reject a `?status=` that is not one of these, so the
     * screen cannot be pointed at a value the schema has never held.
     */
    public const STATUSES = [
        'pending_payment', 'paid', 'partially_refunded', 'refunded', 'cancelled', 'failed',
    ];

    /**
     * Rows per page.
     *
     * Vacancies stop arriving; orders do not. A list that loads every order
     * ever placed is fine on the day it ships and unusable in year two, and
     * the fix is cheaper now than the incident later.
     */
    private const PER_PAGE = 50;

    /** Payment rows that actually represent money received. */
    private const RECEIVED = 'paid';

    public function index()
    {
        $status = (string) $this->request->getGet('status');
        if (! in_array($status, self::STATUSES, true)) {
            $status = '';
        }
        $page = max(1, (int) $this->request->getGet('page'));

        $builder = (new OrderModel())
            ->select('orders.*, u.email AS user_email, u.first_name AS user_first, u.last_name AS user_last')
            ->join('users u', 'u.id = orders.user_id', 'left')
            // Newest first by id rather than by placed_at: placed_at is
            // nullable, and where a null sorts differs by engine, so an order
            // that failed before it was placed would appear at a different end
            // of the list on SQLite and on MySQL.
            ->orderBy('orders.id', 'DESC');

        if ($status !== '') {
            $builder->where('orders.status', $status);
        }

        // One row more than a page is asked for, purely to answer "is there a
        // next page" without a second COUNT over a table that only grows.
        $rows    = $builder->findAll(self::PER_PAGE + 1, ($page - 1) * self::PER_PAGE);
        $hasMore = count($rows) > self::PER_PAGE;

        return view('Modules\Admin\Views\orders\index', [
            'title'    => 'Orders',
            'active'   => 'orders',
            'rows'     => array_slice($rows, 0, self::PER_PAGE),
            'statuses' => self::STATUSES,
            'summary'  => $this->summary(),
            'current'  => $status,
            'page'     => $page,
            'hasMore'  => $hasMore,
        ]);
    }

    public function show($id)
    {
        $order = (new OrderModel())->find((int) $id);
        if ($order === null) {
            return redirect()->to(site_url('admin/orders'))->with('error', 'Order not found.');
        }

        $db      = db_connect();
        $orderId = (int) $order['id'];

        $items   = $db->table('order_items')->where('order_id', $orderId)->orderBy('id', 'ASC')->get()->getResultArray();
        $itemIds = array_map('intval', array_column($items, 'id'));

        // Enrolments hang off the *line*, not the order, because one line of
        // four seats is four learners with four accounts. Grouped by line so
        // the view can print each of them under the thing they hold a place on.
        $enrolments = [];
        if ($itemIds !== []) {
            $rows = $db->table('enrolments e')
                ->select('e.*, u.email, u.first_name, u.last_name, u.status AS user_status, c.title AS course_title, cs.start_date, cs.end_date')
                ->join('users u', 'u.id = e.user_id', 'left')
                ->join('courses c', 'c.id = e.course_id', 'left')
                ->join('course_sessions cs', 'cs.id = e.session_id', 'left')
                ->whereIn('e.order_item_id', $itemIds)
                ->orderBy('e.id', 'ASC')
                ->get()->getResultArray();

            foreach ($rows as $row) {
                $enrolments[(int) $row['order_item_id']][] = $row;
            }
        }

        $payments = $db->table('payments')->where('order_id', $orderId)->orderBy('id', 'ASC')->get()->getResultArray();
        $refunds  = $db->table('refunds')->where('order_id', $orderId)->orderBy('id', 'ASC')->get()->getResultArray();

        return view('Modules\Admin\Views\orders\show', [
            'title'      => 'Order ' . $order['order_no'],
            'active'     => 'orders',
            'order'      => $order,
            'items'      => $items,
            'enrolments' => $enrolments,
            'payments'   => $payments,
            'refunds'    => $refunds,
            'invoice'    => $db->table('invoices')->where('order_id', $orderId)->get()->getRowArray(),
            'coupon'     => $order['coupon_id']
                ? $db->table('coupons')->where('id', (int) $order['coupon_id'])->get()->getRowArray()
                : null,
            'received'   => $this->receivedCents($payments),
            'refunded'   => $this->refundedCents($refunds),
        ]);
    }

    /**
     * Confirm a bank transfer: record what arrived, then fulfil the order.
     *
     * The whole of the guarding is in the order of these two steps and in the
     * reference. Recording first means a fulfilment that fails leaves evidence
     * that the money was taken; fulfilling first would mean a crash between the
     * two produced enrolments with no payment behind them.
     */
    public function markPaid($id)
    {
        $orders = new OrderModel();
        $order  = $orders->find((int) $id);
        if ($order === null) {
            return redirect()->to(site_url('admin/orders'))->with('error', 'Order not found.');
        }

        $back = site_url('admin/orders/' . (int) $order['id']);

        // A cancelled or refunded order is a decision somebody made, and a
        // transfer arriving against one is a conversation, not a button. An
        // already-paid order is refused here rather than left to fulfil()'s
        // idempotency guard, so that a mis-click on the wrong row says what
        // happened instead of silently recording a second payment.
        if (! in_array((string) $order['status'], ['pending_payment', 'failed'], true)) {
            return redirect()->to($back)->with(
                'error',
                'This order is ' . str_replace('_', ' ', (string) $order['status']) . ' and cannot be marked paid.'
            );
        }

        if (! $this->validate([
            'reference' => ['label' => 'Bank reference', 'rules' => 'required|max_length[191]'],
            'received'  => ['label' => 'Date received', 'rules' => 'permit_empty|valid_date'],
            'note'      => ['label' => 'Note', 'rules' => 'permit_empty|max_length[500]'],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $reference = trim((string) $this->request->getPost('reference'));
        $note      = trim((string) $this->request->getPost('note'));
        $received  = trim((string) $this->request->getPost('received'));
        $admin     = session()->get('admin_user') ?? [];
        $payments  = new PaymentModel();

        try {
            $payment = $payments->recordOnce([
                'order_id'    => (int) $order['id'],
                'gateway'     => 'bank',
                'gateway_ref' => $reference,
                'status'      => self::RECEIVED,
                // The order's own total, deliberately, with no box to type a
                // different figure into. A short payment is not a paid order,
                // and an amount field here is a way to enrol somebody who has
                // paid half of it. A genuine part payment is a conversation
                // with the buyer, not a click.
                'amount_cents' => (int) $order['total_cents'],
                'currency'     => strtoupper((string) $order['currency']),
                // A bank sends no payload, so what is kept verbatim here is the
                // human record instead: who confirmed it, when, and anything
                // they wanted the next person to know. That is the evidence a
                // dispute is answered from, and it belongs on the payment row
                // rather than in the order's notes where it would be edited.
                'raw_payload_json' => json_encode([
                    'source'      => 'admin',
                    'recorded_by' => (string) ($admin['email'] ?? 'unknown'),
                    'recorded_at' => date('c'),
                    'reference'   => $reference,
                    'note'        => $note,
                ], JSON_UNESCAPED_UNICODE),
                'received_at' => $received !== '' ? date('Y-m-d H:i:s', (int) strtotime($received)) : date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // recordOnce() looks before it inserts, so two administrators
            // confirming the same transfer at the same moment can both look,
            // both find nothing, and let the unique key on
            // (gateway, gateway_ref) decide. Losing that race is a duplicate.
            if ($payments->findByRef('bank', $reference) !== null) {
                return redirect()->to($back)->with('error', $this->duplicateMessage($reference, (int) $order['id']));
            }

            log_message('error', 'Could not record a bank payment for order {no}: {msg}', [
                'no'  => (string) $order['order_no'],
                'msg' => $e->getMessage(),
            ]);

            return redirect()->to($back)->with('error', 'The payment could not be recorded. Nothing has been changed.');
        }

        // Null means this reference has been recorded before. Stop: the first
        // recording already fulfilled the order, and doing it again is how one
        // transfer becomes two sets of enrolments and two confirmation emails.
        if ($payment === null) {
            return redirect()->to($back)->with('error', $this->duplicateMessage($reference, (int) $order['id']));
        }

        // The only call on this screen that turns a payment into a seat. Seats,
        // order status, enrolments, learner accounts and the invoice all move
        // together inside its transaction; nothing above touches any of them.
        $outcome = (new EnrolmentService())->fulfil((int) $order['id']);

        if (! $outcome['ok']) {
            log_message('error', 'Fulfilment failed for order {no} after an administrator recorded a bank transfer ({reason}); the payment is recorded and the enrolment is not made', [
                'no'     => (string) $order['order_no'],
                'reason' => (string) $outcome['reason'],
            ]);

            return redirect()->to($back)->with(
                'error',
                'The payment is recorded, but the enrolments could not be created. Nobody has been enrolled — tell a developer before trying again.'
            );
        }

        if ($outcome['already']) {
            return redirect()->to($back)->with(
                'message',
                'Payment recorded. This order had already been fulfilled, so no new enrolments were created.'
            );
        }

        log_message('info', 'Order {no} fulfilled from a bank transfer recorded by {who} ({count} enrolments)', [
            'no'    => (string) $order['order_no'],
            'who'   => (string) ($admin['email'] ?? 'unknown'),
            'count' => count($outcome['enrolments']),
        ]);

        return redirect()->to($back)->with('message', sprintf(
            'Payment recorded and the order fulfilled. %d enrolment%s created; joining instructions have been sent.',
            count($outcome['enrolments']),
            count($outcome['enrolments']) === 1 ? '' : 's'
        ));
    }

    /**
     * Record a refund that has already been made in the gateway's dashboard.
     *
     * A full refund gives the seats back and cancels the enrolments; a partial
     * one records the money and leaves both alone, because a discount applied
     * after the fact is not somebody leaving the class.
     *
     * The order's status is not decoration in that decision. `InventoryService::
     * reconcile()` recomputes `seats_sold` from order items whose order is
     * `paid` or `partially_refunded` — so a fully refunded order *must* end up
     * at `refunded`, or the next `spark seats:reconcile` would put the seats
     * this method just released straight back on the sold pile. That the two
     * agree is the reason the status is written here at all.
     */
    public function refund($id)
    {
        $orders = new OrderModel();
        $order  = $orders->find((int) $id);
        if ($order === null) {
            return redirect()->to(site_url('admin/orders'))->with('error', 'Order not found.');
        }

        $back = site_url('admin/orders/' . (int) $order['id']);
        $db   = db_connect();

        // money() is a view helper and a controller is not a view; the refusal
        // messages below quote a figure back, and an unloaded helper here is a
        // fatal error on the one path an administrator only reaches when
        // something has already gone wrong.
        helper('commerce');

        if (! $this->validate([
            'payment_id'  => ['label' => 'Payment', 'rules' => 'required|is_natural_no_zero'],
            'amount'      => ['label' => 'Amount', 'rules' => 'required|max_length[24]'],
            'gateway_ref' => ['label' => 'Refund reference', 'rules' => 'required|max_length[191]'],
            'reason'      => ['label' => 'Reason', 'rules' => 'permit_empty|max_length[255]'],
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $reference = trim((string) $this->request->getPost('gateway_ref'));
        $paymentId = (int) $this->request->getPost('payment_id');

        // Scoped to this order, so a payment id from another order cannot be
        // posted in and have its money written against this one.
        $payment = $db->table('payments')
            ->where('id', $paymentId)->where('order_id', (int) $order['id'])
            ->get()->getRowArray();
        if ($payment === null) {
            return redirect()->to($back)->with('error', 'That payment does not belong to this order.');
        }

        // The refunds table has no unique key to lean on, so the duplicate
        // check is here. It matters more than it looks: without it a double
        // submit records the money twice *and* releases the seats twice, and
        // the second release is invisible because unsell() clamps at zero.
        if ($db->table('refunds')->where('order_id', (int) $order['id'])->where('gateway_ref', $reference)->countAllResults() > 0) {
            return redirect()->to($back)->with('error', 'A refund with reference "' . $reference . '" is already recorded against this order.');
        }

        $amount = $this->cents((string) $this->request->getPost('amount'));
        if ($amount === null || $amount <= 0) {
            return redirect()->back()->withInput()->with('error', 'Enter the refund amount as a plain figure, for example 495 or 495.00.');
        }

        $payments = $db->table('payments')->where('order_id', (int) $order['id'])->get()->getResultArray();
        $refunds  = $db->table('refunds')->where('order_id', (int) $order['id'])->get()->getResultArray();
        $room     = $this->receivedCents($payments) - $this->refundedCents($refunds);

        if ($amount > $room) {
            return redirect()->back()->withInput()->with(
                'error',
                'That is more than is left to refund on this order (' . money($room, (string) $order['currency']) . ').'
            );
        }

        // "Full" means nothing is left owing back, not that the figure matches
        // the order total: two partial refunds that add up to the whole sale
        // are a full refund and the seats should go back on the second one.
        $isFull = ($amount === $room);

        $items = $db->table('order_items')->where('order_id', (int) $order['id'])->orderBy('id', 'ASC')->get()->getResultArray();
        $now   = date('Y-m-d H:i:s');
        $admin = session()->get('admin_user') ?? [];

        $inventory = new InventoryService($db);
        $touched   = [];

        $db->transStart();

        $db->table('refunds')->insert([
            'payment_id'   => (int) $payment['id'],
            'order_id'     => (int) $order['id'],
            'amount_cents' => $amount,
            'currency'     => strtoupper((string) $order['currency']),
            'reason'       => mb_substr(trim((string) $this->request->getPost('reason')), 0, 255),
            // Recorded, not requested: the money has already moved in the
            // gateway's dashboard by the time anybody reaches this form.
            'status'       => 'processed',
            'gateway_ref'  => $reference,
            'processed_at' => $now,
            'created_at'   => $now,
        ]);

        if ($isFull) {
            foreach ($items as $item) {
                if (empty($item['session_id'])) {
                    continue;
                }
                // The only thing on this screen allowed near a seat count. It
                // opens its own transaction, which nests safely inside this one
                // on CodeIgniter's transaction counter.
                $inventory->unsell((int) $item['session_id'], (int) $item['qty']);
                $touched[] = (int) $item['session_id'];
            }

            $enrolments = new EnrolmentModel();
            foreach ($enrolments->whereIn('order_item_id', array_map('intval', array_column($items, 'id')) ?: [0])
                ->whereIn('status', ['active', 'completed'])->findAll() as $enrolment) {
                $enrolments->update((int) $enrolment['id'], ['status' => 'cancelled']);
            }
        }

        // See the docblock: this line is what keeps reconcile() from undoing
        // the release above. `paid_at` is deliberately left alone — the money
        // was received, and erasing that would lose the date it arrived.
        $orders->update((int) $order['id'], ['status' => $isFull ? 'refunded' : 'partially_refunded']);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to($back)->with('error', 'The refund could not be recorded. Nothing has been changed.');
        }

        // Outside the transaction, as EnrolmentService does it: a class that
        // has just dropped below full should reopen, but that is a consequence
        // and not worth holding a lock for.
        foreach (array_unique($touched) as $sessionId) {
            $inventory->refreshStatus($sessionId);
        }

        log_message('info', 'Refund of {amount} recorded on order {no} by {who} ({kind})', [
            'amount' => $amount,
            'no'     => (string) $order['order_no'],
            'who'    => (string) ($admin['email'] ?? 'unknown'),
            'kind'   => $isFull ? 'full' : 'partial',
        ]);

        return redirect()->to($back)->with('message', $isFull
            ? 'Full refund recorded. The seats have been released and the enrolments cancelled.'
            : 'Partial refund recorded. The seats and enrolments are unchanged.');
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Count and value of every order, by status and by currency.
     *
     * Per currency, and never added together: LKR 250,000 plus USD 495 is not a
     * number, and a single "awaiting payment" figure would be the most
     * confidently wrong thing on the screen.
     *
     * @return array<string, array{count:int, money:array<string,int>}>
     */
    private function summary(): array
    {
        $rows = db_connect()->table('orders')
            ->select('status, currency, COUNT(*) AS n, COALESCE(SUM(total_cents), 0) AS cents', false)
            ->groupBy('status')->groupBy('currency')
            ->get()->getResultArray();

        $out = [];
        foreach (self::STATUSES as $status) {
            $out[$status] = ['count' => 0, 'money' => []];
        }

        foreach ($rows as $row) {
            // A status this build does not list — an older order, somebody's
            // two-in-the-morning UPDATE — is still counted rather than dropped,
            // or the totals silently stop adding up to the table below them.
            $status = (string) $row['status'];
            $out[$status] ??= ['count' => 0, 'money' => []];

            $currency = strtoupper((string) $row['currency']);
            $out[$status]['count'] += (int) $row['n'];
            $out[$status]['money'][$currency] = ($out[$status]['money'][$currency] ?? 0) + (int) $row['cents'];
        }

        return $out;
    }

    /** @param list<array> $payments */
    private function receivedCents(array $payments): int
    {
        // Only `paid`. A row marked `mismatch` by the webhook handler is
        // evidence of a payment that was refused, and counting it would make an
        // order look settled that nobody has been enrolled on.
        return array_sum(array_map(
            static fn (array $p): int => (string) $p['status'] === self::RECEIVED ? (int) $p['amount_cents'] : 0,
            $payments
        ));
    }

    /** @param list<array> $refunds */
    private function refundedCents(array $refunds): int
    {
        return array_sum(array_map(
            static fn (array $r): int => in_array((string) $r['status'], ['failed', 'cancelled'], true) ? 0 : (int) $r['amount_cents'],
            $refunds
        ));
    }

    /**
     * A typed amount, in minor units, without a float going anywhere near it.
     *
     * `(int) ((float) '495.95' * 100)` is 49594 on a normal build, because
     * 495.95 is not representable in binary and the cast truncates rather than
     * rounds. Money is an integer everywhere in this codebase precisely to
     * avoid that, and the one field where a human types a decimal point is the
     * one place the discipline could be undone.
     *
     * @return int|null null when what was typed is not an amount at all
     */
    private function cents(string $input): ?int
    {
        $clean = preg_replace('/[^0-9.,]/', '', trim($input)) ?? '';

        if (str_contains($clean, '.') && str_contains($clean, ',')) {
            // Both separators present: whichever comes last is the decimal
            // point and the other groups the thousands, which is true of both
            // the 1,234.56 and the 1.234,56 convention.
            $clean = strrpos($clean, '.') > strrpos($clean, ',')
                ? str_replace(',', '', $clean)
                : str_replace(['.', ','], ['', '.'], $clean);
        } elseif (preg_match('/^\d+,\d{1,2}$/', $clean) === 1) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace(',', '', $clean);
        }

        if (preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $clean, $m) !== 1) {
            return null;
        }

        return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '', 2, '0');
    }

    /**
     * What to say when a bank reference has already been recorded.
     *
     * Naming the other order matters: one transfer settling two invoices is an
     * ordinary thing a finance department does, and the unique key on
     * (gateway, gateway_ref) will refuse the second one. The administrator
     * needs to know it is not a double-click before they invent a reference.
     */
    private function duplicateMessage(string $reference, int $orderId): string
    {
        $existing = (new PaymentModel())->findByRef('bank', $reference);

        if ($existing !== null && (int) $existing['order_id'] !== $orderId) {
            $other = (new OrderModel())->find((int) $existing['order_id']);

            return 'Reference "' . $reference . '" is already recorded against order '
                . ($other['order_no'] ?? ('#' . (int) $existing['order_id']))
                . '. Nothing has been changed. If one transfer settled both orders, record this one under a reference that distinguishes it.';
        }

        return 'Reference "' . $reference . '" is already recorded against this order. Nothing has been changed.';
    }
}
