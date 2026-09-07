<?php

namespace Modules\Commerce\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Commerce\Libraries\CartContext;
use Modules\Commerce\Models\OrderModel;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\Services\Gateways\GatewayRegistry;
use Modules\Commerce\Services\InventoryService;

/**
 * Checkout: billing, attendees, payment hand-off, and the order afterwards.
 *
 * Four decisions are encoded here, and each of them is the difference between a
 * checkout that works for a real training business and one that only works in a
 * demonstration.
 *
 *   **The browser returning from a gateway proves nothing.** `returned()` marks
 *   nothing paid, creates no enrolment and touches no seat. It shows the order
 *   exactly as it stands: paid if a verified webhook has already landed, and
 *   "we are waiting for confirmation, and we are holding your seats" if it has
 *   not. Fulfilment lives in `EnrolmentService::fulfil()` and is reached only
 *   from a verified webhook or from an administrator. A return URL can be
 *   replayed, forged from an address bar, or simply never arrive because the
 *   buyer closed the tab on a train.
 *
 *   **Seats are inventory owned by `InventoryService`.** Nothing in this class
 *   writes `seats_sold` or `seats_reserved`. `index()` reads availability only
 *   to warn; `place()` delegates the authoritative re-check to
 *   `CheckoutService::place()`, which takes the lock. Every seat-related
 *   failure sends the buyer back to the cart with the actual reason, while they
 *   can still choose another date.
 *
 *   **An attendee is a person, not a quantity.** The buyer is routinely not the
 *   learner: a manager buying four seats is buying them for four people with
 *   four email addresses, and the joining instructions, the recording and the
 *   certificate all belong to them rather than to the person who paid. So the
 *   form asks for a name and an email per seat, and `place()` rebuilds them as
 *   `[cartItemId => [['name' => …, 'email' => …], …]]` for the order line.
 *
 *   **The idempotency key is issued by this session, never accepted from the
 *   request.** `CheckoutService::place()` hands back the existing order for a
 *   key it has already seen, which is exactly what makes a double-click safe —
 *   and exactly what would let somebody read another buyer's order by posting
 *   their key. The key is minted in `index()`, stored in the session, and the
 *   posted value is honoured only when it matches the one this session was
 *   issued.
 */
class Checkout extends BaseController
{
    /** Where this session's checkout key lives while the form is on screen. */
    private const IDEMPOTENCY_SESSION_KEY = 'checkout_idempotency_key';

    /** Attendee rows the form offers per line, however many seats were bought. */
    private const MAX_SEATS_RENDERED = 50;

    // ── The form ────────────────────────────────────────────────────────────

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $cart = CartContext::existing();
        if ($cart === null) {
            return redirect()->to(locale_url('cart'));
        }

        $checkout = new CheckoutService();
        $totals   = $checkout->totals($cart);

        if ($totals['items'] === []) {
            return redirect()->to(locale_url('cart'));
        }

        $gateways = GatewayRegistry::forCurrency((string) $cart['currency']);

        return view('Modules\Commerce\Views\checkout\index', [
            'cart'     => $cart,
            'totals'   => $totals,
            'gateways' => $gateways,
            // Read-only availability, so somebody whose hold lapsed while they
            // were deciding is told here rather than after they have typed four
            // attendees in. The authoritative check happens under a lock in
            // CheckoutService::place().
            'warnings' => $this->seatWarnings($totals['items'], (int) $cart['id']),
            'user'     => LearnerAuth::user(),
            // A fresh key per rendered form. Every submission of *this* form —
            // the double click, the mobile browser re-POSTing what it thought
            // timed out, the back button — carries the same one and therefore
            // produces one order.
            'idempotencyKey'  => $this->issueIdempotencyKey(),
            'errors'          => session()->getFlashdata('errors') ?? [],
            'crumbs'          => [
                ['label' => lang('Commerce.cart.title'), 'url' => locale_url('cart')],
                ['label' => lang('Commerce.checkout.title')],
            ],
            'title'           => lang('Commerce.checkout.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Commerce.checkout.meta'),
            // A checkout has nothing to offer a crawler and everything to lose
            // by being indexed: it is a page of half-filled forms and prices
            // that belong to one basket.
            'noIndex'         => true,
        ]);
    }

    // ── Placing the order ───────────────────────────────────────────────────

    public function place(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $cart = CartContext::existing();
        if ($cart === null) {
            return redirect()->to(locale_url('cart'));
        }

        $checkout = new CheckoutService();
        $totals   = $checkout->totals($cart);
        if ($totals['items'] === []) {
            return redirect()->to(locale_url('cart'))->with('error', lang('Commerce.checkout.empty'));
        }

        // The gateway is validated against what was actually offered, not
        // against a list of names. A posted `gateway=stripe` when Stripe has no
        // keys would otherwise start a payment that fails at the worst possible
        // moment and in the least legible way — the exact outcome the registry
        // exists to prevent.
        $keys = array_map(
            static fn ($g) => $g->key(),
            GatewayRegistry::forCurrency((string) $cart['currency'])
        );
        if ($keys === []) {
            return redirect()->back()->withInput()->with('error', lang('Commerce.checkout.no_gateway'));
        }

        // One pass over everything, so a buyer who has both mistyped an email
        // and missed the terms box is told about both rather than about
        // whichever the code happened to check first.
        if (! $this->validate($this->rulesFor($totals['items'], $keys))) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $gateway   = (string) $this->request->getPost('gateway');
        $attendees = $this->readAttendees($totals['items']);

        // Two seats named to the same address is a typo, and a silent one:
        // EnrolmentService de-duplicates attendees by email, so the order would
        // be paid for four seats and produce three enrolments with nobody
        // noticing until somebody was turned away at the door.
        if (($clash = $this->duplicateEmail($attendees)) !== null) {
            return redirect()->back()->withInput()
                ->with('errors', [$clash => lang('Commerce.checkout.err_attendee_duplicate')]);
        }

        $result = $checkout->place(
            $cart,
            $this->billingFrom($cart),
            $attendees,
            $gateway,
            $this->idempotencyKey()
        );

        if (! $result['ok']) {
            // Anything beginning with seat_ came from the locked re-check: the
            // seat went while this buyer was typing. They go back to the cart,
            // where the date can be changed, rather than to a payment page for
            // something that is no longer for sale.
            if (str_starts_with($result['reason'], 'seat_')) {
                return redirect()->to(locale_url('cart'))
                    ->with('error', $this->seatFailureMessage(substr($result['reason'], 5)));
            }

            if ($result['reason'] === 'empty') {
                return redirect()->to(locale_url('cart'))->with('error', lang('Commerce.checkout.empty'));
            }

            return redirect()->back()->withInput()->with('error', lang('Commerce.checkout.err_place'));
        }

        // `existing` means this key had already been used — the second click, or
        // a re-POST. Between the two, a webhook may have landed and settled the
        // order, and starting a fresh payment against a paid order is how a
        // school takes the same money twice.
        $order = $result['order'];
        if (! empty($order['paid_at']) || $order['status'] !== 'pending_payment') {
            return redirect()->to(locale_url('order/' . $order['order_no']));
        }

        return $this->handOff($order, $gateway);
    }

    /**
     * Pay, or pay again, for an order that has already been placed.
     *
     * The cancel link on every gateway comes back here, as does the buyer who
     * closed the tab on the payment page and returned to the confirmation email
     * an hour later. Nothing is re-placed: the order exists, its seats are
     * still held, and this only starts a fresh payment attempt against it.
     */
    public function pay(?string $locale = null, ?string $orderNo = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $order = $this->mustSee($orderNo);

        // Already settled. Sending somebody to a payment page for an order that
        // is paid is how a school takes the same money twice.
        if (! empty($order['paid_at']) || $order['status'] !== 'pending_payment') {
            return redirect()->to(locale_url('order/' . $order['order_no']));
        }

        $offered = GatewayRegistry::forCurrency((string) $order['currency']);
        $keys    = array_map(static fn ($g) => $g->key(), $offered);

        $requested = (string) ($this->request->getGet('method') ?? '');
        $gateway   = in_array($requested, $keys, true)
            ? $requested
            : (string) ($order['gateway'] ?? '');

        if (! in_array($gateway, $keys, true)) {
            return redirect()->to(locale_url('order/' . $order['order_no']))
                ->with('error', lang('Commerce.checkout.no_gateway'));
        }

        return $this->handOff($order, $gateway);
    }

    // ── Coming back ─────────────────────────────────────────────────────────

    /**
     * The buyer's return from a gateway. It confirms nothing.
     *
     * There is deliberately no fulfilment here, no seat commit, no status
     * change and no email — not even "the gateway told us it succeeded", since
     * the gateway told the *browser*, and a browser can be made to say
     * anything. What the buyer sees is the order as the database currently
     * holds it: paid when a verified webhook has already landed, and an honest
     * "we are waiting, and your seats are held" when it has not.
     */
    public function returned(?string $locale = null, ?string $orderNo = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        return $this->renderOrder($this->mustSee($orderNo), true);
    }

    public function order(?string $locale = null, ?string $orderNo = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        return $this->renderOrder($this->mustSee($orderNo), false);
    }

    /**
     * The printable invoice.
     *
     * Available before payment as well as after, because a bank-transfer buyer
     * needs a document to hand their finance department *in order* to pay. An
     * order with no row in `invoices` yet is printed as a proforma against its
     * order number rather than being given an invoice number it has not earned:
     * the invoice series is gapless accounting, issued once at fulfilment, and
     * minting a number for an order that may never be paid puts a hole in it.
     */
    public function invoice(?string $locale = null, ?string $orderNo = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $order   = $this->mustSee($orderNo);
        $orders  = new OrderModel();
        $invoice = db_connect()->table('invoices')->where('order_id', (int) $order['id'])->get()->getRowArray();

        return view('Modules\Commerce\Views\checkout\invoice', [
            'order'   => $order,
            'items'   => $orders->items((int) $order['id']),
            'invoice' => $invoice,
            'billing' => $this->billingOf($order),
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Start a payment and show whatever the gateway needs the browser to do.
     *
     * The three shapes a gateway can return — follow a URL, auto-submit a form,
     * read some instructions — are all rendered by one view, because the buyer
     * should see the same page whichever they are about to use and because a
     * gateway that fails to start (`instructions` with an error message, from
     * Stripe or PayPal) has to land somewhere that offers them another way to
     * pay rather than a blank screen.
     */
    private function handOff(array $order, string $gatewayKey)
    {
        $gateway = GatewayRegistry::find($gatewayKey);
        if ($gateway === null) {
            return redirect()->to(locale_url('order/' . $order['order_no']))
                ->with('error', lang('Commerce.checkout.no_gateway'));
        }

        $orders  = new OrderModel();
        $items   = $orders->items((int) $order['id']);

        $handoff = $gateway->begin(
            $order,
            $items,
            locale_url('checkout/return/' . $order['order_no']),
            locale_url('order/' . $order['order_no'])
        );

        return view('Modules\Commerce\Views\checkout\pay', [
            'order'    => $order,
            'items'    => $items,
            'gateway'  => $gateway,
            'handoff'  => $handoff,
            // Only the alternatives, so the panel underneath never offers the
            // method that has just failed as though it were a fresh idea.
            'others'   => array_values(array_filter(
                GatewayRegistry::forCurrency((string) $order['currency']),
                static fn ($g) => $g->key() !== $gatewayKey
            )),
            'title'    => lang('Commerce.pay.title') . ' — ' . setting('site_name', ''),
            'noIndex'  => true,
        ]);
    }

    private function renderOrder(array $order, bool $justReturned)
    {
        $orders = new OrderModel();

        return view('Modules\Commerce\Views\checkout\order', [
            'order'         => $order,
            'items'         => $orders->items((int) $order['id']),
            'billing'       => $this->billingOf($order),
            'invoice'       => db_connect()->table('invoices')->where('order_id', (int) $order['id'])->get()->getRowArray(),
            'justReturned'  => $justReturned,
            'crumbs'        => [['label' => lang('Commerce.order.title', [$order['order_no']])]],
            'title'         => lang('Commerce.order.title', [$order['order_no']]) . ' — ' . setting('site_name', ''),
            'noIndex'       => true,
        ]);
    }

    /**
     * The order this visitor is allowed to see, or a 404.
     *
     * Two ways in, and never a bare order number:
     *
     *   - the signed-in learner the order belongs to;
     *   - anybody holding both the order number *and* the cart token that
     *     placed it, which is what lets a guest see what they have just bought
     *     without being forced to create an account first.
     *
     * A wrong or missing token is a 404 rather than a 403, so the address does
     * not confirm that an order number exists — order numbers are short and a
     * day's worth of them is guessable.
     */
    private function mustSee(?string $orderNo): array
    {
        $order = (new OrderModel())->findByNumber(trim((string) $orderNo));

        if ($order === null || ! $this->accessible($order)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $order;
    }

    private function accessible(array $order): bool
    {
        $userId = LearnerAuth::id();
        if ($userId !== null && (int) ($order['user_id'] ?? 0) === $userId) {
            return true;
        }

        $token = (string) ($this->request->getCookie(CartContext::COOKIE) ?? '');
        if ($token === '') {
            return false;
        }

        // Only the hash of the cart token is stored on the order — see
        // billingFrom() for why it lives in billing_json, and hash_equals so
        // the comparison cannot be turned into a way of guessing it.
        $stored = (string) ($this->billingOf($order)['cart_token_hash'] ?? '');

        return $stored !== '' && hash_equals($stored, hash('sha256', $token));
    }

    /** @return array<string, mixed> */
    private function billingOf(array $order): array
    {
        $decoded = json_decode((string) $order['billing_json'], true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * The billing contact, as it will be frozen onto the order.
     *
     * `cart_token_hash` rides along because `orders` has no `cart_id` column
     * and a guest still has to be able to reopen the order they have just
     * placed. It is the only field here that is not an address; the views print
     * named fields rather than looping over this array, so it never shows up on
     * an invoice.
     *
     * The tax country is deliberately not taken from what is typed below. Tax
     * is computed by `CheckoutService::totals()` from the country frozen onto
     * the cart, so the total on the summary is the total charged; letting a
     * self-declared billing country change it at the last step would move the
     * figure after the buyer had agreed to it.
     */
    private function billingFrom(array $cart): array
    {
        $post = static fn (string $key, int $max): string => mb_substr(
            trim((string) (service('request')->getPost($key) ?? '')),
            0,
            $max
        );

        return [
            'name'            => $post('billing_name', 128),
            'email'           => strtolower($post('billing_email', 191)),
            'phone'           => $post('billing_phone', 48),
            'company'         => $post('billing_company', 191),
            'address'         => $post('billing_address', 255),
            'city'            => $post('billing_city', 96),
            'postcode'        => $post('billing_postcode', 24),
            'country'         => strtoupper(mb_substr($post('billing_country', 2), 0, 2)),
            'tax_id'          => $post('billing_tax_id', 64),
            'po_number'       => $post('billing_po_number', 64),
            'notes'           => $post('billing_notes', 500),
            'cart_token_hash' => hash('sha256', (string) $cart['token']),
        ];
    }

    /**
     * Rebuild the attendee grid into what `CheckoutService::place()` expects:
     * `[cartItemId => [['name' => …, 'email' => …], …]]`.
     *
     * Driven by the cart rather than by the post, so a submission carrying
     * fifty invented line ids adds nothing: an id that is not in this cart is
     * never read, and seats beyond the quantity paid for are never read either.
     *
     * @param list<array> $items
     * @return array<int, list<array{name:string, email:string}>>
     */
    private function readAttendees(array $items): array
    {
        $posted = $this->request->getPost('attendee');
        $posted = is_array($posted) ? $posted : [];

        $out = [];
        foreach ($items as $item) {
            $itemId = (int) $item['id'];
            $seats  = min((int) $item['qty'], self::MAX_SEATS_RENDERED);
            $rows   = is_array($posted[$itemId] ?? null) ? $posted[$itemId] : [];

            $list = [];
            for ($seat = 0; $seat < $seats; $seat++) {
                $row   = is_array($rows[$seat] ?? null) ? $rows[$seat] : [];
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $name  = trim((string) ($row['name'] ?? ''));

                if ($email === '') {
                    continue;
                }

                $list[] = ['name' => mb_substr($name, 0, 128), 'email' => mb_substr($email, 0, 191)];
            }

            if ($list !== []) {
                $out[$itemId] = $list;
            }
        }

        return $out;
    }

    /**
     * The field name of the first repeated attendee address, or null.
     *
     * @param array<int, list<array{name:string, email:string}>> $attendees
     */
    private function duplicateEmail(array $attendees): ?string
    {
        $seen = [];
        foreach ($attendees as $itemId => $list) {
            foreach ($list as $seat => $attendee) {
                $email = strtolower($attendee['email']);
                if (isset($seen[$email])) {
                    return 'attendee.' . $itemId . '.' . $seat . '.email';
                }
                $seen[$email] = true;
            }
        }

        return null;
    }

    /**
     * Validation rules, built from the cart so that every seat that was paid
     * for has a person against it.
     *
     * A seat with nobody named is not a saving in typing: `EnrolmentService`
     * falls back to the buyer for an unnamed seat and then de-duplicates by
     * email, so four anonymous seats become one enrolment. Asking here is the
     * only place the answer can still be got cheaply.
     *
     * @param list<array>  $items
     * @param list<string> $gatewayKeys the gateways this currency was offered
     */
    private function rulesFor(array $items, array $gatewayKeys): array
    {
        $rules = [
            'billing_name'    => ['label' => lang('Commerce.checkout.name'), 'rules' => 'required|max_length[128]'],
            'billing_email'   => ['label' => lang('Commerce.checkout.email'), 'rules' => 'required|valid_email|max_length[191]'],
            'billing_phone'   => ['label' => lang('Commerce.checkout.phone'), 'rules' => 'permit_empty|max_length[48]'],
            'billing_country' => ['label' => lang('Commerce.checkout.country'), 'rules' => 'required|exact_length[2]|alpha'],
            'gateway'         => ['label' => lang('Commerce.checkout.payment'), 'rules' => 'required|in_list[' . implode(',', $gatewayKeys) . ']'],
            // Not a formality: it is the record that this buyer was shown the
            // transfer, cancellation and recording terms before paying, and it
            // is the first thing asked for when somebody disputes a charge.
            'terms'           => ['label' => lang('Commerce.checkout.terms_label'), 'rules' => 'required'],
        ];

        foreach ($items as $item) {
            $seats = min((int) $item['qty'], self::MAX_SEATS_RENDERED);
            for ($seat = 0; $seat < $seats; $seat++) {
                $base = 'attendee.' . (int) $item['id'] . '.' . $seat;

                $rules[$base . '.name'] = [
                    'label' => lang('Commerce.checkout.attendee_name_n', [$seat + 1]),
                    'rules' => 'required|max_length[128]',
                ];
                $rules[$base . '.email'] = [
                    'label' => lang('Commerce.checkout.attendee_email_n', [$seat + 1]),
                    'rules' => 'required|valid_email|max_length[191]',
                ];
            }
        }

        return $rules;
    }

    /**
     * Which lines no longer have the seats the cart thinks it holds.
     *
     * Read-only and advisory. It exists so the page can say "two of the three
     * seats you had have gone" while the buyer can still act on it; the
     * decision is taken under a lock in `CheckoutService::place()` and this
     * never pretends otherwise.
     *
     * @param list<array> $items
     * @return array<int, array{left:int|null, qty:int}>
     */
    private function seatWarnings(array $items, int $cartId): array
    {
        $sessionIds = array_map(
            'intval',
            array_column(array_filter($items, static fn ($i) => $i['item_type'] === 'session'), 'item_id')
        );

        if ($sessionIds === []) {
            return [];
        }

        // The cart's own hold is excluded, so a cart holding the last seat is
        // not warned that the last seat has gone.
        $left = (new InventoryService())->seatsLeftFor($sessionIds, $cartId);

        $out = [];
        foreach ($items as $item) {
            if ($item['item_type'] !== 'session') {
                continue;
            }
            $available = $left[(int) $item['item_id']] ?? null;
            if ($available !== null && $available < (int) $item['qty']) {
                $out[(int) $item['id']] = ['left' => $available, 'qty' => (int) $item['qty']];
            }
        }

        return $out;
    }

    private function seatFailureMessage(string $reason): string
    {
        return match ($reason) {
            'gone'    => lang('Commerce.checkout.seat_gone'),
            'short'   => lang('Commerce.checkout.seat_short'),
            'closed'  => lang('Commerce.checkout.seat_closed'),
            'missing' => lang('Commerce.checkout.seat_missing'),
            default   => lang('Commerce.checkout.seat_gone'),
        };
    }

    /** Mint the key for a freshly rendered form and remember it. */
    private function issueIdempotencyKey(): string
    {
        $key = bin2hex(random_bytes(16));
        session()->set(self::IDEMPOTENCY_SESSION_KEY, $key);

        return $key;
    }

    /**
     * The key this submission may use.
     *
     * The posted value is honoured only when it is the one this session was
     * issued. `CheckoutService::place()` hands back the existing order for a
     * key it has seen before — which is what makes the second click harmless,
     * and what would otherwise let anybody read a stranger's order by posting
     * their key. A mismatch is not an error: it gets a fresh key and therefore
     * a fresh order, which is the safe direction to be wrong in.
     */
    private function idempotencyKey(): string
    {
        $issued = (string) (session()->get(self::IDEMPOTENCY_SESSION_KEY) ?? '');
        $posted = (string) ($this->request->getPost('idempotency_key') ?? '');

        if ($issued !== '' && $posted !== '' && hash_equals($issued, $posted)) {
            return $issued;
        }

        return $this->issueIdempotencyKey();
    }
}
