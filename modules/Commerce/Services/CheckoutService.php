<?php

namespace Modules\Commerce\Services;

use CodeIgniter\Database\BaseConnection;
use Modules\Commerce\Models\CartItemModel;
use Modules\Commerce\Models\CartModel;
use Modules\Commerce\Models\MembershipPlanModel;
use Modules\Commerce\Models\OrderItemModel;
use Modules\Commerce\Models\OrderModel;

/**
 * Putting things in a basket, and turning the basket into an order.
 *
 * The order of operations matters more than anything else in this class:
 *
 *   **Seats are held before a price is quoted, not after.** Adding a course to
 *   the cart takes the seat. If the hold fails there is nothing to price and
 *   the visitor is told immediately, while they are still on the course page
 *   and can pick another date — rather than at the end of a checkout they have
 *   already typed four attendees into.
 *
 *   **Placing an order does not release the hold.** The seats stay held, under
 *   the same cart, until payment is confirmed and they become sold. Releasing
 *   at order time and re-taking at payment time opens a window in which
 *   somebody who has just entered their card details can lose the seat to
 *   somebody who has not.
 *
 *   **Nothing here marks an order paid.** That is `EnrolmentService`, called by
 *   a verified webhook or by an administrator. A checkout that can conclude a
 *   sale on its own is a checkout that can be made to conclude one by anybody
 *   who can construct a request.
 */
class CheckoutService
{
    private BaseConnection $db;
    private InventoryService $inventory;
    private PricingService $pricing;

    public function __construct(?InventoryService $inventory = null, ?PricingService $pricing = null, ?BaseConnection $db = null)
    {
        $this->db        = $db ?? db_connect();
        $this->inventory = $inventory ?? new InventoryService($this->db);
        $this->pricing   = $pricing ?? new PricingService($this->db);
    }

    // ── The cart ────────────────────────────────────────────────────────────

    /**
     * Put `qty` seats on a session into a cart.
     *
     * @return array{ok:bool, reason:string, left:int|null}
     *         reason ∈ ok | gone | short | closed | missing | unpriced
     */
    public function addSession(array $cart, int $sessionId, int $qty = 1): array
    {
        $qty = max(1, min(50, $qty));

        $price = $this->pricing->sessionPrice($sessionId, (string) $cart['currency']);
        if ($price === null) {
            // A session with no price in this visitor's currency has not been
            // priced for their market. Better to say so than to convert one and
            // invent a number nobody chose.
            return ['ok' => false, 'reason' => 'unpriced', 'left' => null];
        }

        $hold = $this->inventory->hold($sessionId, $qty, (int) $cart['id']);
        if (! $hold['ok']) {
            return ['ok' => false, 'reason' => $hold['reason'], 'left' => $hold['left']];
        }

        $items = new CartItemModel();
        $existing = $items->where('cart_id', (int) $cart['id'])
            ->where('item_type', 'session')->where('item_id', $sessionId)->first();

        $row = [
            'cart_id'          => (int) $cart['id'],
            'item_type'        => 'session',
            'item_id'          => $sessionId,
            'qty'              => $qty,
            'unit_price_cents' => $price['price_cents'],
            'discount_cents'   => 0,
            'meta_json'        => json_encode(['compare_at_cents' => $price['compare_at_cents']]),
        ];

        if ($existing !== null) {
            $items->update((int) $existing['id'], $row);
        } else {
            $items->insert($row);
        }

        $this->reprice($cart);

        return ['ok' => true, 'reason' => 'ok', 'left' => $hold['left']];
    }

    /**
     * Put a bundle into a cart.
     *
     * A bundle takes no seats: it is a promise of several courses whose dates
     * the learner picks afterwards, from their account. Trying to hold seats on
     * four courses at the moment somebody buys a certificate programme would
     * mean choosing four dates for them, which is precisely what a programme is
     * sold to avoid.
     *
     * @return array{ok:bool, reason:string}
     */
    public function addBundle(array $cart, int $bundleId, int $qty = 1): array
    {
        $price = $this->pricing->bundlePrice($bundleId, (string) $cart['currency']);
        if ($price === null) {
            return ['ok' => false, 'reason' => 'unpriced'];
        }

        $items    = new CartItemModel();
        $existing = $items->where('cart_id', (int) $cart['id'])
            ->where('item_type', 'bundle')->where('item_id', $bundleId)->first();

        $row = [
            'cart_id'          => (int) $cart['id'],
            'item_type'        => 'bundle',
            'item_id'          => $bundleId,
            'qty'              => max(1, min(50, $qty)),
            'unit_price_cents' => $price['price_cents'],
            'discount_cents'   => 0,
            'meta_json'        => json_encode(['compare_at_cents' => $price['compare_at_cents']]),
        ];

        $existing !== null ? $items->update((int) $existing['id'], $row) : $items->insert($row);

        $this->reprice($cart);

        return ['ok' => true, 'reason' => 'ok'];
    }

    /**
     * Put a membership plan in the basket.
     *
     * Quantity is always one. A membership is a term on one person's account:
     * two of them is not two passes, it is the same pass with the money taken
     * twice — and `MembershipService::record()` writes one row per order line
     * on purpose, so a quantity of two would charge twice and deliver once.
     * Somebody who wants longer buys a longer plan, which is cheaper anyway.
     *
     * A basket holds one plan at a time for the same reason. Choosing the
     * annual after the monthly is changing your mind, not adding to an order.
     *
     * @return array{ok:bool, reason:string}
     */
    public function addMembership(array $cart, int $planId, string $currency): array
    {
        $plan = (new MembershipPlanModel())->priced($planId, $currency);
        if ($plan === null) {
            // Unpublished, or not priced in this currency — never a zero line.
            return ['ok' => false, 'reason' => 'unpriced'];
        }

        $items = new CartItemModel();

        foreach ($items->where('cart_id', (int) $cart['id'])->where('item_type', 'membership')->findAll() as $old) {
            $items->delete((int) $old['id']);
        }

        $items->insert([
            'cart_id'          => (int) $cart['id'],
            'item_type'        => 'membership',
            'item_id'          => (int) $plan['id'],
            'qty'              => 1,
            'unit_price_cents' => (int) $plan['price_cents'],
            'discount_cents'   => 0,
            'meta_json'        => json_encode([
                'compare_at_cents' => $plan['compare_at_cents'],
                'months'           => (int) $plan['months'],
                'code'             => $plan['code'],
            ]),
        ]);

        $this->reprice($cart);

        return ['ok' => true, 'reason' => 'ok'];
    }

    public function removeItem(array $cart, int $itemId): void
    {
        $items = new CartItemModel();
        $item  = $items->where('cart_id', (int) $cart['id'])->where('id', $itemId)->first();
        if ($item === null) {
            return;
        }

        if ($item['item_type'] === 'session') {
            $this->inventory->release((int) $cart['id'], (int) $item['item_id']);
        }
        $items->delete($itemId);

        $this->reprice($cart);
    }

    /**
     * Recompute the automatic discounts on every line.
     *
     * Run after any change to the cart, because the rules read the cart: three
     * seats is a group booking and two is not, so removing one seat has to be
     * able to take the group discount away again.
     */
    public function reprice(array $cart): void
    {
        $items = new CartItemModel();

        foreach ($items->where('cart_id', (int) $cart['id'])->findAll() as $item) {
            $session = $item['item_type'] === 'session'
                ? $this->db->table('course_sessions')->where('id', (int) $item['item_id'])->get()->getRowArray()
                : [];

            $best = $this->pricing->autoDiscount([
                'session'          => $session ?: [],
                'item_type'        => $item['item_type'],
                'qty'              => (int) $item['qty'],
                'unit_price_cents' => (int) $item['unit_price_cents'],
                'currency'         => (string) $cart['currency'],
                'user'             => $cart['user_id'] ? ['id' => (int) $cart['user_id']] : null,
                'account'          => null,
            ]);

            $items->update((int) $item['id'], ['discount_cents' => $best['discount_cents']]);
        }
    }

    /**
     * The full money picture for a cart.
     *
     * @return array{subtotal_cents:int, discount_cents:int, coupon_cents:int, tax_cents:int, tax_label:string, total_cents:int, currency:string, items:list<array>, count:int}
     */
    public function totals(array $cart): array
    {
        $carts   = new CartModel();
        $summary = $carts->summary((int) $cart['id']);

        $subtotal = $summary['subtotal_cents'];
        $discount = $summary['discount_cents'];

        $couponCents = 0;
        if (! empty($cart['coupon_id'])) {
            $coupon = $this->db->table('coupons')->where('id', (int) $cart['coupon_id'])->get()->getRowArray();
            if ($coupon !== null) {
                $check = $this->pricing->applyCoupon(
                    (string) $coupon['code'],
                    max(0, $subtotal - $discount),
                    (string) $cart['currency'],
                    $cart['user_id'] ? (int) $cart['user_id'] : null
                );
                $couponCents = $check['ok'] ? $check['discount_cents'] : 0;
            }
        }

        $net = max(0, $subtotal - $discount - $couponCents);
        $tax = $this->pricing->tax($net, $cart['country'] ?? null);

        return [
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'coupon_cents'   => $couponCents,
            'tax_cents'      => $tax['tax_cents'],
            'tax_label'      => $tax['label'],
            'total_cents'    => $net + $tax['tax_cents'],
            'currency'       => (string) $cart['currency'],
            'items'          => $summary['items'],
            'count'          => $summary['count'],
        ];
    }

    // ── Placing the order ───────────────────────────────────────────────────

    /**
     * Turn a cart into an order awaiting payment.
     *
     * `idempotencyKey` is supplied by the checkout form and is unique on the
     * table, so the impatient second click — and the mobile browser that
     * re-POSTs what it thinks timed out — produce one order rather than two,
     * with no deduplication logic to get wrong later.
     *
     * @param array $billing   name, email, phone, company, address, country
     * @param array $attendees per cart-item id, a list of {name, email} — one per seat
     *
     * @return array{ok:bool, reason:string, order:?array}
     */
    public function place(array $cart, array $billing, array $attendees, string $gateway, string $idempotencyKey): array
    {
        // t_field() resolves the JSON locale maps into the snapshot titles
        // below. A service is not a view, so nothing has loaded the helper yet.
        helper('norlanka');

        $orders = new OrderModel();

        // Already placed under this key: hand back the same order rather than
        // making a second one. This is the whole point of the key, and it has
        // to be checked before anything else touches the database.
        $existing = $orders->where('idempotency_key', $idempotencyKey)->first();
        if ($existing !== null) {
            return ['ok' => true, 'reason' => 'existing', 'order' => $existing];
        }

        $totals = $this->totals($cart);
        if ($totals['items'] === []) {
            return ['ok' => false, 'reason' => 'empty', 'order' => null];
        }

        // Re-check every seat under a lock before taking money. The cart's hold
        // may have expired while the buyer was typing, and the honest failure
        // is here — before a payment page — rather than after.
        foreach ($totals['items'] as $item) {
            if ($item['item_type'] !== 'session') {
                continue;
            }
            $refresh = $this->inventory->hold((int) $item['item_id'], (int) $item['qty'], (int) $cart['id']);
            if (! $refresh['ok']) {
                return ['ok' => false, 'reason' => 'seat_' . $refresh['reason'], 'order' => null];
            }
        }

        $this->db->transStart();

        $orderId = $orders->insert([
            'order_no'        => $orders->nextNumber(),
            'user_id'         => $cart['user_id'] ?: null,
            'status'          => 'pending_payment',
            'currency'        => $totals['currency'],
            'country'         => $cart['country'] ?? null,
            'subtotal_cents'  => $totals['subtotal_cents'],
            'discount_cents'  => $totals['discount_cents'] + $totals['coupon_cents'],
            'tax_cents'       => $totals['tax_cents'],
            'total_cents'     => $totals['total_cents'],
            'coupon_id'       => $cart['coupon_id'] ?: null,
            'billing_json'    => json_encode($billing, JSON_UNESCAPED_UNICODE),
            'gateway'         => $gateway,
            // Bank transfer is the one route that legitimately waits: the seat
            // is held to the due date rather than for fifteen minutes, because
            // a company's finance department does not pay within the hour.
            'due_at'          => $gateway === 'bank' ? date('Y-m-d H:i:s', time() + 7 * 86400) : null,
            'placed_at'       => date('Y-m-d H:i:s'),
            'idempotency_key' => $idempotencyKey,
        ], true);

        $orderItems = new OrderItemModel();
        foreach ($totals['items'] as $item) {
            $lineNet = (int) $item['unit_price_cents'] * (int) $item['qty'] - (int) $item['discount_cents'];
            $lineTax = $this->pricing->tax(max(0, $lineNet), $cart['country'] ?? null);

            $orderItems->insert([
                'order_id'           => $orderId,
                'item_type'          => $item['item_type'],
                'item_id'            => (int) $item['item_id'],
                'course_id'          => $item['item_type'] === 'session'
                    ? (int) ($this->db->table('course_sessions')->select('course_id')->where('id', (int) $item['item_id'])->get()->getRowArray()['course_id'] ?? 0)
                    : null,
                'session_id'         => $item['item_type'] === 'session' ? (int) $item['item_id'] : null,
                'bundle_id'          => $item['item_type'] === 'bundle' ? (int) $item['item_id'] : null,
                'qty'                => (int) $item['qty'],
                // Frozen at the moment of sale. A course renamed, rescheduled or
                // withdrawn two years from now must not rewrite the history of
                // somebody's receipt.
                'title_snapshot'     => mb_substr((string) t_field($item['course_title'] ?? ''), 0, 255),
                'meta_snapshot_json' => json_encode([
                    'mode'       => $item['session_mode'] ?? null,
                    'start_date' => $item['start_date'] ?? null,
                    'end_date'   => $item['end_date'] ?? null,
                    'timezone'   => $item['timezone'] ?? null,
                    'venue'      => $item['venue_name'] ?? null,
                    'city'       => $item['venue_city'] ?? null,
                    // Frozen with the rest of the line, and for the same
                    // reason: fulfilment reads the term from here rather than
                    // from the plan, so re-pricing a plan or changing its
                    // length can never alter a pass somebody has already paid
                    // for — including one whose webhook arrives afterwards.
                    'months'     => $item['item_type'] === 'membership' ? (int) ($item['months'] ?? 0) : null,
                ], JSON_UNESCAPED_UNICODE),
                'unit_price_cents'   => (int) $item['unit_price_cents'],
                'discount_cents'     => (int) $item['discount_cents'],
                'tax_cents'          => $lineTax['tax_cents'],
                'total_cents'        => max(0, $lineNet) + $lineTax['tax_cents'],
                // The buyer is routinely not the learner. Four seats on one
                // order are four different people with four different email
                // addresses, and the joining instructions go to them.
                'attendee_json'      => json_encode(
                    $attendees[$item['id']] ?? [],
                    JSON_UNESCAPED_UNICODE
                ),
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return ['ok' => false, 'reason' => 'db', 'order' => null];
        }

        return ['ok' => true, 'reason' => 'ok', 'order' => $orders->find($orderId)];
    }
}
