<?php

namespace Modules\Commerce\Controllers;

use App\Controllers\BaseController;
use DateTimeImmutable;
use DateTimeZone;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Commerce\Libraries\CartContext;
use Modules\Commerce\Models\CartItemModel;
use Modules\Commerce\Models\CartModel;
use Modules\Commerce\Services\CheckoutService;
use Modules\Commerce\Services\InventoryService;
use Modules\Commerce\Services\PricingService;
use Throwable;

/**
 * The basket.
 *
 * Four decisions are encoded here, and three of them are the opposite of the
 * obvious version.
 *
 * **The page never creates a cart.** `index()` calls `CartContext::existing()`,
 * which does not write and does not set a cookie. A basket that springs into
 * being because somebody looked at the basket page means a `Set-Cookie` on a
 * GET, a row per crawler, and a catalogue that can no longer be cached at the
 * edge because every response varies by cookie. Only `add()` — the one route
 * where a visitor has actually chosen something — calls `current()`.
 *
 * **Every refusal gets its own sentence.** `CheckoutService` hands back a
 * reason — gone, short, closed, missing, unpriced — and collapsing those into
 * "sorry, something went wrong" throws away the only useful information in the
 * failure. "The last seat went while you were reading the page" is an ordinary
 * thing that happens to honest buyers, and what it deserves is another date,
 * not an apology.
 *
 * **Nothing here touches seat counts.** A quantity change goes back through
 * `CheckoutService::addSession()`, which takes the seats under a lock before it
 * writes the line. Editing `cart_items.qty` here would leave the hold at the
 * old number and quietly oversell the class — which is a bug nobody sees until
 * thirteen people arrive for twelve chairs.
 *
 * **Nothing here can conclude a sale.** The basket is a list. An order is
 * placed by `Checkout`, and only `EnrolmentService::fulfil()` — reached from a
 * verified webhook or from an administrator recording a bank transfer — ever
 * marks one paid.
 */
class Cart extends BaseController
{
    /** Matching the seats box on the course page; beyond this it is a quote. */
    private const MAX_QTY = 50;

    /** Coupon attempts per minute, per address. */
    private const COUPON_TRIES = 8;

    /** The warnings that would make `CheckoutService::place()` refuse this cart. */
    private const BLOCKING = ['missing', 'cancelled', 'closed', 'gone', 'short'];

    // ── The page ────────────────────────────────────────────────────────────

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $cart = CartContext::existing();

        // A cookie with no cart behind it means the cart expired — a fortnight
        // untouched, or an administrator cleared it. Worth distinguishing:
        // "your basket is empty" shown to somebody who put four seats in it two
        // weeks ago reads as a bug rather than as a policy.
        $stale = $cart === null
            && trim((string) ($this->request->getCookie(CartContext::COOKIE) ?? '')) !== '';

        if ($cart === null) {
            return $this->render(['stale' => $stale]);
        }

        $totals = (new CheckoutService())->totals($cart);
        if ($totals['items'] === []) {
            return $this->render(['stale' => false]);
        }

        $state = $this->decorate($totals['items'], $cart);
        $zone  = $this->displayZone();

        $lines = $state['lines'];
        foreach ($lines as &$line) {
            if ($line['held_until'] === null) {
                $line['held_until_label'] = null;
                $line['held_until_iso']   = null;

                continue;
            }

            // `expires_at` was written with date(), so it is already in the
            // application's own timezone; parsing it without naming a zone
            // reads it back in that same one, which stays correct even if the
            // application's timezone is changed later.
            $moment                   = new DateTimeImmutable((string) $line['held_until']);
            $line['held_until_iso']   = $moment->format(DATE_ATOM);
            // Bare clock on the line. The banner above carries the zone these
            // times are on, so the row does not repeat it.
            $line['held_until_label'] = $moment->setTimezone($zone)->format('H:i');
        }
        unset($line);

        return $this->render([
            'cart'           => $cart,
            'lines'          => $lines,
            'totals'         => $totals,
            'coupon'         => $this->appliedCoupon($cart),
            'blocked'        => $state['blocked'],
            // Named with its zone: a hold expires at a moment, and a moment
            // printed without one is a moment somebody reads wrong.
            'holdUntilLabel' => $state['hold_until'] === null
                ? null
                : (new DateTimeImmutable((string) $state['hold_until']))->setTimezone($zone)->format('H:i T'),
        ]);
    }

    // ── Changing the basket ─────────────────────────────────────────────────

    /**
     * Put a session or a bundle in the basket.
     *
     * This is the only action on the site that may start a cart, because it is
     * the only one where somebody has said what they want.
     */
    public function add(?string $locale = null)
    {
        helper(['norlanka', 'commerce', 'url']);

        $type = (string) $this->request->getPost('item_type');
        $id   = (int) $this->request->getPost('item_id');

        if (! in_array($type, ['session', 'bundle'], true) || $id <= 0) {
            return redirect()->back()->with('error', lang('Commerce.cart.fail.bad_request'));
        }

        // Clamped rather than rejected. Somebody who typed 200 into the seats
        // box wants a lot of seats, and the useful answer to that is a booking
        // for as many as we can take plus the corporate route, not a red
        // validation message.
        $qty = max(1, min(self::MAX_QTY, (int) $this->request->getPost('qty')));

        $cart     = CartContext::current();
        $checkout = new CheckoutService();

        $result = $type === 'bundle'
            ? $checkout->addBundle($cart, $id, $qty)
            : $checkout->addSession($cart, $id, $qty);

        if (! $result['ok']) {
            // Back rather than on to the basket: the buyer is standing on the
            // page that lists the other dates for this course, which is exactly
            // what somebody who has just lost a seat needs to see next.
            return redirect()->back()->with('error', $this->reasonMessage($result, $cart, $qty));
        }

        return redirect()->to(locale_url('cart'))->with('notice', lang('Commerce.cart.added'));
    }

    /** Change how many seats a line is for. */
    public function update(?string $locale = null)
    {
        helper(['norlanka', 'commerce', 'url']);

        $cart = CartContext::existing();
        if ($cart === null) {
            return redirect()->to(locale_url('cart'));
        }

        // Scoped to this cart. Without the `cart_id` condition the id in a form
        // field addresses anybody's basket line, and a cart token is the only
        // thing standing between a guest basket and a stranger.
        $line = (new CartItemModel())
            ->where('cart_id', (int) $cart['id'])
            ->where('id', (int) $this->request->getPost('item'))
            ->first();

        if ($line === null) {
            return redirect()->to(locale_url('cart'))->with('error', lang('Commerce.cart.fail.not_in_cart'));
        }

        $checkout = new CheckoutService();
        $qty      = (int) $this->request->getPost('qty');

        // Zero seats is a removal. Making somebody hunt for a different control
        // to express it is how an unwanted line survives all the way to the
        // card form.
        if ($qty <= 0) {
            $checkout->removeItem($cart, (int) $line['id']);

            return redirect()->to(locale_url('cart'))->with('notice', lang('Commerce.cart.removed'));
        }

        $qty = min(self::MAX_QTY, $qty);

        // Back through the service rather than writing `qty` here: the seats
        // have to be re-held at the new number, under the lock, before the line
        // is allowed to claim them. A failed hold leaves the line exactly as it
        // was, so the basket never shows seats nobody is holding.
        $result = $line['item_type'] === 'bundle'
            ? $checkout->addBundle($cart, (int) $line['item_id'], $qty)
            : $checkout->addSession($cart, (int) $line['item_id'], $qty);

        return redirect()->to(locale_url('cart'))->with(
            $result['ok'] ? 'notice' : 'error',
            $result['ok'] ? lang('Commerce.cart.updated') : $this->reasonMessage($result, $cart, $qty)
        );
    }

    public function remove(?string $locale = null)
    {
        helper(['norlanka', 'url']);

        $cart = CartContext::existing();
        if ($cart === null) {
            return redirect()->to(locale_url('cart'));
        }

        // `removeItem()` scopes by cart itself, and releases the seat hold in
        // the same breath — so a date somebody changes their mind about is back
        // on sale at once rather than fifteen minutes later.
        (new CheckoutService())->removeItem($cart, (int) $this->request->getPost('item'));

        return redirect()->to(locale_url('cart'))->with('notice', lang('Commerce.cart.removed'));
    }

    /** Apply or clear a coupon code. */
    public function coupon(?string $locale = null)
    {
        helper(['norlanka', 'commerce', 'url']);

        $cart = CartContext::existing();
        if ($cart === null) {
            return redirect()->to(locale_url('cart'));
        }

        $carts = new CartModel();
        $code  = strtoupper(trim((string) $this->request->getPost('code')));

        if ($this->request->getPost('action') === 'remove' || $code === '') {
            $carts->update((int) $cart['id'], ['coupon_id' => null]);

            return redirect()->to(locale_url('cart'))->with('notice', lang('Commerce.cart.coupon.cleared'));
        }

        // A coupon box with no limit on it is a free oracle: anybody can sit
        // and guess codes until one works, and the codes worth guessing are the
        // memorable ones marketing chose. Eight tries a minute is generous for
        // somebody copying one out of an email and useless to a script.
        if (service('throttler')->check(md5('coupon-' . $this->request->getIPAddress()), self::COUPON_TRIES, MINUTE) === false) {
            return redirect()->to(locale_url('cart'))->with('error', lang('Commerce.cart.coupon.throttled'));
        }

        $totals = (new CheckoutService())->totals($cart);

        // Checked against the same basis `totals()` uses — subtotal less the
        // automatic discounts — so the figure quoted here is the one that will
        // appear on the order, not a larger one taken from the gross.
        $check = (new PricingService())->applyCoupon(
            $code,
            max(0, $totals['subtotal_cents'] - $totals['discount_cents']),
            (string) $cart['currency'],
            LearnerAuth::id()
        );

        if (! $check['ok']) {
            return redirect()->to(locale_url('cart'))->with('error', $this->couponMessage($check['reason']));
        }

        $carts->update((int) $cart['id'], ['coupon_id' => (int) $check['coupon']['id']]);

        return redirect()->to(locale_url('cart'))->with(
            'notice',
            lang('Commerce.cart.coupon.applied', [money($check['discount_cents'], (string) $cart['currency'])])
        );
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Add to every line what the page needs in order to be honest about it:
     * how long the seats are held, how many are actually free, and whether the
     * class it points at is still the class it was when it went in.
     *
     * @param list<array> $items rows from CheckoutService::totals()
     *
     * @return array{lines:list<array>, hold_until:?string, blocked:bool}
     */
    private function decorate(array $items, array $cart): array
    {
        $sessionIds = [];
        $bundleIds  = [];
        foreach ($items as $item) {
            if ($item['item_type'] === 'bundle') {
                $bundleIds[] = (int) $item['item_id'];
            } else {
                $sessionIds[] = (int) $item['item_id'];
            }
        }

        $db = db_connect();

        // This cart's own holds, and only the ones still alive. Read as
        // `expires_at > now` rather than trusting the sweeper to have run: a
        // late cron must never be able to make a lapsed hold look current.
        $holds = $sessionIds === [] ? [] : array_column(
            $db->table('seat_holds')
                ->select('session_id, expires_at')
                ->where('cart_id', (int) $cart['id'])
                ->whereIn('session_id', $sessionIds)
                ->where('expires_at >', date('Y-m-d H:i:s'))
                ->get()->getResultArray(),
            'expires_at',
            'session_id'
        );

        // Availability *excluding* this cart's own hold, so a line the visitor
        // is already holding does not report itself as sold out to them.
        $left = $sessionIds === []
            ? []
            : (new InventoryService($db))->seatsLeftFor($sessionIds, (int) $cart['id']);

        // A bundle sits under /certificates or /bootcamps depending on its
        // type, and `summary()` does not carry the type, so the line would
        // otherwise have to link to a guess.
        $bundleTypes = $bundleIds === [] ? [] : array_column(
            $db->table('bundles')->select('id, type')->whereIn('id', $bundleIds)->get()->getResultArray(),
            'type',
            'id'
        );

        $blocked   = false;
        $holdUntil = null;

        foreach ($items as &$item) {
            $item['line_total_cents'] = (int) $item['unit_price_cents'] * (int) $item['qty'] - (int) $item['discount_cents'];
            $item['warning']          = '';
            $item['seats_left']       = null;
            $item['held_until']       = null;
            $item['bundle_type']      = $bundleTypes[$item['item_id']] ?? null;

            // A bundle holds no seats — the learner picks their dates afterwards
            // — so none of what follows applies to one.
            if ($item['item_type'] !== 'session') {
                continue;
            }

            $sessionId          = (int) $item['item_id'];
            $qty                = (int) $item['qty'];
            $item['seats_left'] = $left[$sessionId] ?? null;
            $item['held_until'] = $holds[$sessionId] ?? null;

            // The earliest expiry is the one the banner quotes: it is the
            // deadline that bites first, and the only one worth a headline.
            if ($item['held_until'] !== null && ($holdUntil === null || $item['held_until'] < $holdUntil)) {
                $holdUntil = $item['held_until'];
            }

            // Null rather than empty: the join is a LEFT one, so a null status
            // means the session row is gone, not that it has no status.
            $status = $item['session_status'] === null ? null : (string) $item['session_status'];

            if ($status === null) {
                $item['warning'] = 'missing';
            } elseif ($status === 'cancelled') {
                $item['warning'] = 'cancelled';
            } elseif ($status === 'full') {
                // Its own warning rather than "closed": a class that sold out is
                // a different disappointment from one an administrator withdrew,
                // and the useful next step differs too.
                $item['warning'] = 'gone';
            } elseif (! in_array($status, CourseSessionModel::BOOKABLE, true)) {
                $item['warning'] = 'closed';
            } elseif ($item['held_until'] === null) {
                // The hold has lapsed. Whether that matters depends entirely on
                // whether anybody took the seats in the meantime, so the page
                // says which of the two happened rather than raising the alarm
                // either way.
                //
                // Deliberately not re-taken here. This is a GET, and a page view
                // that writes to `seat_holds` is a page view a crawler can use to
                // hold out a classroom. Checkout re-takes every seat under a
                // lock, which is where a hold belongs.
                $free = $item['seats_left'];

                if ($free === null || $free >= $qty) {
                    $item['warning'] = 'lapsed';
                } else {
                    $item['warning'] = $free <= 0 ? 'gone' : 'short';
                }
            }

            if (in_array($item['warning'], self::BLOCKING, true)) {
                // Checkout re-checks every seat under a lock and would refuse
                // this basket. Saying so here is kinder than saying it after
                // four attendee names have been typed in.
                $blocked = true;
            }
        }
        unset($item);

        return ['lines' => $items, 'hold_until' => $holdUntil, 'blocked' => $blocked];
    }

    /**
     * The coupon attached to the cart, for the panel to name.
     *
     * Read separately because `totals()` returns the money a coupon is worth
     * but not which coupon it was — and a panel that shows a discount without
     * showing the code it came from cannot offer to remove it.
     */
    private function appliedCoupon(array $cart): ?array
    {
        if (empty($cart['coupon_id'])) {
            return null;
        }

        return db_connect()->table('coupons')
            ->select('id, code')
            ->where('id', (int) $cart['coupon_id'])
            ->get()->getRowArray();
    }

    /**
     * A specific sentence for a specific refusal.
     *
     * @param array{ok:bool, reason:string, left?:int|null} $result
     */
    private function reasonMessage(array $result, array $cart, int $requested): string
    {
        return match ($result['reason']) {
            'gone'     => lang('Commerce.cart.fail.gone'),
            'short'    => lang('Commerce.cart.fail.short', [(int) ($result['left'] ?? 0), $requested]),
            'closed'   => lang('Commerce.cart.fail.closed'),
            'missing'  => lang('Commerce.cart.fail.missing'),
            'unpriced' => lang('Commerce.cart.fail.unpriced', [(string) $cart['currency']]),
            default    => lang('Commerce.cart.fail.unknown'),
        };
    }

    /**
     * Why a coupon did not take.
     *
     * No amount is quoted back for `min_spend`: the minimum is stored without a
     * currency of its own, and printing a figure in the wrong one would be an
     * invented number on a page about money.
     */
    private function couponMessage(string $reason): string
    {
        return match ($reason) {
            'not_yet'        => lang('Commerce.cart.coupon.not_yet'),
            'expired'        => lang('Commerce.cart.coupon.expired'),
            'used_up'        => lang('Commerce.cart.coupon.used_up'),
            'wrong_currency' => lang('Commerce.cart.coupon.wrong_currency'),
            'min_spend'      => lang('Commerce.cart.coupon.min_spend'),
            'per_user'       => lang('Commerce.cart.coupon.per_user'),
            default          => lang('Commerce.cart.coupon.unknown'),
        };
    }

    /**
     * The clock the school runs on.
     *
     * A hold expires at a moment, and a moment printed without a zone is a
     * moment somebody reads wrong. The application stores in UTC, so the raw
     * figure would say 09:02 to a buyer in Colombo whose seats go at 14:32.
     */
    private function displayZone(): DateTimeZone
    {
        try {
            return new DateTimeZone((string) setting('timezone', 'Asia/Colombo'));
        } catch (Throwable) {
            // A mistyped setting must not take the basket down.
            return new DateTimeZone('Asia/Colombo');
        }
    }

    /** One render path, so the empty basket and the full one cannot drift apart. */
    private function render(array $data)
    {
        helper(['norlanka', 'url']);

        return view('Modules\Commerce\Views\cart\index', $data + [
            'cart'            => null,
            'lines'           => [],
            'totals'          => null,
            'coupon'          => null,
            'stale'           => false,
            'blocked'         => false,
            'holdUntilLabel'  => null,
            // Passed rather than repeated in the markup, so the number in the
            // seats box and the number the controller clamps to cannot drift.
            'maxQty'          => self::MAX_QTY,
            'crumbs'          => [['label' => lang('Commerce.cart.heading')]],
            'title'           => lang('Commerce.cart.heading') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Commerce.cart.meta'),
            // A basket is personal, is different every minute, and is worth
            // nothing to a search engine. Left indexable it would also compete
            // with the course page that fed it.
            'noIndex'         => true,
        ]);
    }
}
