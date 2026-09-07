<?php

namespace Modules\Commerce\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Commerce\Models\OrderModel;
use Modules\Commerce\Models\PaymentModel;
use Modules\Commerce\Services\Gateways\GatewayRegistry;
use Modules\Learning\Services\EnrolmentService;
use Throwable;

/**
 * Payment webhooks: the one address on this site that can conclude a sale.
 *
 * Everything else in the checkout is a conversation with a browser, and a
 * browser proves nothing. The return URL from a gateway can be replayed out of
 * a history list, typed into an address bar, or never arrive at all because the
 * buyer closed the tab on a train. So `Checkout::returned()` marks nothing paid
 * and creates no enrolment; this does, and only after a signature has been
 * checked against a secret that only the gateway and this server hold.
 *
 * That makes this the most attacked address on the site, and it is written for
 * that rather than for the happy path:
 *
 *   - **It never renders.** No view, no layout, no locale. The reader is a
 *     machine, and an error page here is a map of the application drawn for
 *     whoever asked for one.
 *   - **It never logs an unverified payload.** Anybody at all can POST here,
 *     and a handler that writes back what it was sent, at error level, is a
 *     handler somebody can use to fill the disk and take the site down without
 *     ever finding a bug in it.
 *   - **It never trusts an amount.** A delivery can carry a valid signature,
 *     name a real order, say it was paid — and still be wrong. Checking the sum
 *     against the order is the difference between a webhook handler and a way
 *     of buying a five-day course for a penny.
 *   - **It answers 200 to everything it has finished with**, duplicates and
 *     refusals included. The status code tells a gateway whether to stop
 *     retrying, not whether we were pleased about what arrived; a 500 on a
 *     delivery already dealt with buys days of redelivery and nothing else.
 *
 * Idempotency has two locks, one behind the other. The unique key on
 * `(gateway, gateway_ref)` — see `PaymentModel::recordOnce()` — turns a second
 * delivery of the same event into a no-op here; `orders.paid_at`, taken under a
 * row lock inside `EnrolmentService::fulfil()`, turns a second *fulfilment* of
 * the same order into one too. Either alone would leave a gap, because gateways
 * redeliver in bursts when their own queues drain and two deliveries can be in
 * flight at the same instant.
 *
 * CSRF is exempted for `webhooks/*` in Config\Filters — a gateway has no
 * session and no token to send — which is precisely why the signature check
 * below is not optional.
 */
class Webhooks extends BaseController
{
    /**
     * How much of the raw delivery is kept, in bytes.
     *
     * `payments.raw_payload_json` is a TEXT column, which tops out at 65,535
     * bytes: an oversized payload is silently truncated by MySQL, or rejected
     * outright in strict mode, and losing the whole payment record because one
     * field was too long would be the worst trade available. Cut it here, where
     * the loss is visible, rather than letting the database decide.
     */
    private const MAX_PAYLOAD = 60000;

    public function receive(?string $gateway = null): ResponseInterface
    {
        $key = strtolower(trim((string) $gateway));

        // Resolved by key alone, and deliberately not against the list of
        // gateways currently offered at checkout: a payment begun the hour
        // before a gateway was switched off is still a payment, and refusing
        // its webhook would take the money without giving the seat.
        $adapter = GatewayRegistry::find($key);
        if ($adapter === null || ! $adapter->isConfigured()) {
            // A status line, not an exception. PageNotFoundException would
            // render the site's own 404 — layout, header, navigation, theme —
            // to something that wanted three digits.
            //
            // `bank` passes this check, because it is always configured; its
            // verifyWebhook() then returns null, since no bank sends one.
            return $this->plain(404, 'unknown gateway');
        }

        /** @var IncomingRequest $request */
        $request = $this->request;

        // The signature check. Every adapter returns null rather than throwing
        // for anything it cannot prove — including a genuine delivery whose
        // verification call failed on the network, see PayPalGateway — so null
        // here means "not proven", which is not the same as "forged" and is
        // treated the same way regardless.
        $result = $adapter->verifyWebhook($request);
        if ($result === null) {
            // One fixed-length line, with not a byte of what was posted in it.
            // Anybody can reach this address; a note per rejection is a log,
            // whereas the payload would be a disk somebody else controls.
            log_message('warning', 'Unverified {gateway} webhook from {ip}', [
                'gateway' => $key,
                'ip'      => $request->getIPAddress(),
            ]);

            // Not 200: this delivery has *not* been handled. Nobody retries a
            // forgery, and a real delivery whose verification timed out should
            // come back rather than be quietly acknowledged.
            return $this->plain(400, 'not verified');
        }

        $orders = new OrderModel();
        $order  = $orders->findByNumber((string) $result['order_no']);
        if ($order === null) {
            // Verified, so this is not noise off the internet: a gateway we
            // share a secret with has taken money against an order number this
            // database does not hold. An order is committed before the buyer is
            // ever sent to a gateway, so there is no race that explains this.
            // Loud, and 200 — retrying will not make the order exist, and the
            // reference below is enough to find the payment in the gateway's
            // own console and refund or reconcile it by hand.
            log_message('error', 'Verified {gateway} webhook for unknown order {no} (ref {ref})', [
                'gateway' => $key,
                'no'      => (string) $result['order_no'],
                'ref'     => (string) $result['gateway_ref'],
            ]);

            return $this->plain(200, 'unknown order');
        }

        $payments = new PaymentModel();

        try {
            $payment = $payments->recordOnce([
                'order_id'         => (int) $order['id'],
                'gateway'          => $key,
                'gateway_ref'      => (string) $result['gateway_ref'],
                'status'           => (string) $result['status'],
                'amount_cents'     => (int) $result['amount_cents'],
                'currency'         => strtoupper((string) $result['currency']),
                'raw_payload_json' => $this->payload($request),
                'received_at'      => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            // recordOnce() looks before it inserts, which leaves a window: two
            // deliveries of one event, arriving together, both look, both find
            // nothing, and the unique key on (gateway, gateway_ref) lets
            // exactly one of them in. Losing that race is a duplicate and gets
            // the duplicate's answer. Anything else is a real failure and must
            // come back, so the gateway is told to retry.
            if ($payments->findByRef($key, (string) $result['gateway_ref']) !== null) {
                return $this->plain(200, 'duplicate');
            }

            log_message('error', 'Could not record {gateway} payment for order {no}: {msg}', [
                'gateway' => $key,
                'no'      => (string) $order['order_no'],
                'msg'     => $e->getMessage(),
            ]);

            return $this->plain(500, 'not recorded');
        }

        // Null means this (gateway, gateway_ref) has been seen before. Stop:
        // the first delivery already did all of it, and 200 is the only thing
        // that makes a gateway's retry queue let go.
        if ($payment === null) {
            return $this->plain(200, 'duplicate');
        }

        // A verified delivery that names a real order and still has the wrong
        // sum on it is either our bug — the wrong amount sent to the gateway
        // when the payment began — or somebody replaying a cheap order's event
        // against an expensive order number. Enrolling on either is worse than
        // refusing both, and a mismatch nobody is told about is a hole that
        // stays open for as long as it takes somebody to read a report.
        $paidCents    = (int) $result['amount_cents'];
        $paidCurrency = strtoupper((string) $result['currency']);
        $dueCents     = (int) $order['total_cents'];
        $dueCurrency  = strtoupper((string) $order['currency']);

        if ($paidCents !== $dueCents || $paidCurrency !== $dueCurrency) {
            // The row stays: it is the evidence, and the payload with it. But
            // it is not a payment *for this order* and must never read as one
            // in a reconciliation.
            $payments->update((int) $payment['id'], ['status' => 'mismatch']);

            log_message(
                'error',
                'Refused {gateway} webhook on order {no}: paid {paid} {pc}, expected {due} {dc} (ref {ref})',
                [
                    'gateway' => $key,
                    'no'      => (string) $order['order_no'],
                    'paid'    => $paidCents,
                    'pc'      => $paidCurrency,
                    'due'     => $dueCents,
                    'dc'      => $dueCurrency,
                    'ref'     => (string) $result['gateway_ref'],
                ]
            );

            // Handled, and therefore 200. There is nothing a redelivery of the
            // same wrong figure could improve.
            return $this->plain(200, 'amount mismatch');
        }

        // The only call in the application that turns a payment into a seat.
        // Seats, order status, enrolments, accounts and the invoice all move
        // together inside its transaction; nothing here touches any of them.
        $outcome = (new EnrolmentService())->fulfil((int) $order['id']);

        if (! $outcome['ok']) {
            // The payment row is already written, so a redelivery of this event
            // will be turned away as a duplicate above and will never reach
            // fulfilment a second time. The gateway's retry queue cannot rescue
            // this order; only a person can. Hence error level, and hence still
            // 200 — days of retries would add noise and change nothing.
            log_message('error', 'Fulfilment failed for order {no} after a verified {gateway} payment ({reason}); the money is taken and the enrolment is not made', [
                'no'      => (string) $order['order_no'],
                'gateway' => $key,
                'reason'  => (string) $outcome['reason'],
            ]);

            return $this->plain(200, 'recorded, not fulfilled');
        }

        // Already paid: an administrator recorded a bank transfer, or a
        // *different* reference for the same order arrived first. Not an error
        // and not a duplicate — the payment above is a new fact worth keeping —
        // but nothing was fulfilled here and the log should not claim it was.
        if ($outcome['already']) {
            return $this->plain(200, 'already fulfilled');
        }

        // Info rather than silence: this is the line that answers "when did
        // that order actually go through, and on which gateway" months later,
        // and it is bounded at one per sale.
        log_message('info', 'Order {no} fulfilled from a {gateway} webhook ({count} enrolments)', [
            'no'      => (string) $order['order_no'],
            'gateway' => $key,
            'count'   => count($outcome['enrolments']),
        ]);

        return $this->plain(200, 'ok');
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * What the gateway sent, as it sent it, for the dispute nobody expects.
     *
     * Stripe and PayPal post a JSON body; PayHere posts a form, which has no
     * body worth keeping, so its fields are re-encoded instead. Cut to a length
     * the column can hold — see MAX_PAYLOAD — on a byte boundary that cannot
     * split a UTF-8 character in half, because a payload that will not decode
     * is a payload that answers nothing.
     */
    private function payload(IncomingRequest $request): string
    {
        $body = (string) ($request->getBody() ?? '');
        if ($body === '') {
            $body = (string) json_encode($request->getPost());
        }

        return mb_strcut($body, 0, self::MAX_PAYLOAD);
    }

    /**
     * A status code and three words.
     *
     * The body is only ever read by a person looking at a gateway's delivery
     * log, so it says what happened and carries nothing else: an order number
     * in there would be an order number visible to everybody who can open that
     * console, including the gateway's own support staff.
     */
    private function plain(int $status, string $body): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setContentType('text/plain; charset=UTF-8')
            ->setBody($body);
    }
}
