<?php

namespace Modules\Commerce\Controllers;

use App\Controllers\BaseController;
use Modules\Commerce\Libraries\CartContext;
use Modules\Commerce\Models\CartModel;
use Modules\Commerce\Services\PricingService;

/**
 * The visitor saying which currency they want to be quoted in.
 *
 * Everything else about currency on this site is a guess — a Cloudflare country
 * header, a region subtag on Accept-Language, the default price book. This is
 * the one place a person answers the question themselves, and
 * `PricingService::resolveCurrency()` ranks the answer above every guess for a
 * year afterwards. Somebody in Colombo paying a London employer's invoice in
 * dollars is not an edge case, and no header will ever work that out.
 *
 * Three decisions are encoded here.
 *
 * **A choice is not a conversion.** Prices are published per currency and never
 * converted at runtime, so switching does not re-label the same numbers: every
 * line in the basket is re-read at the other currency's own published price,
 * and anything that has no price in it is dropped. That is
 * `CartContext::switchCurrency()`, and the drop has to be *said*, because a
 * basket that quietly loses a line between two page loads is a basket the buyer
 * stops trusting.
 *
 * **The referrer is a header, not a destination.** Sending the visitor back to
 * where they came from is the whole point of a switcher that lives in the site
 * header, and `redirect()->to($referrer)` is the open redirect that turns it
 * into somebody else's phishing link — an email saying "your invoice is ready",
 * pointing at this domain, landing on theirs. Only the path and query survive
 * `backTo()`, and only after the host has been checked.
 *
 * **It is a GET, and that is deliberate.** The route is a plain link, so it
 * works without JavaScript, is reachable from the keyboard and can be put in
 * the footer of an email. Writing on a GET is normally worth arguing about; the
 * only thing this writes is a display preference the same visitor can change
 * back in one click, and nothing here can spend money or move a seat.
 */
class Currency extends BaseController
{
    /**
     * How long the choice is remembered.
     *
     * A year, because a currency is a standing preference and not a session
     * detail: somebody who chose dollars in March should not be quoted rupees
     * in April because a cookie lapsed while they were away.
     */
    private const COOKIE_DAYS = 365;

    public function set(?string $locale = null, ?string $code = null)
    {
        helper(['norlanka', 'commerce', 'url']);

        $code = strtoupper(trim((string) $code));

        // Validated against what is actually on sale, not against a list of
        // ISO codes: a currency with no active row has no published prices
        // behind it, so honouring it would empty every basket it touched and
        // blank the price on every course page.
        $active = array_map('strtoupper', array_column(currency_options(), 'code'));

        if (! in_array($code, $active, true)) {
            // A sentence and the page they were on, rather than a 404. This
            // address is an ordinary link in the header and on the basket, and
            // the way it goes wrong in practice is a currency being switched
            // off in the admin while somebody still has the old page open —
            // which deserves an explanation, not the loss of their place.
            return redirect()->to($this->backTo())
                ->with('error', lang('Commerce.currency.unknown'));
        }

        // httpOnly is off here, and this is the only cookie on the site where
        // that is true. The header's switcher has to be able to show which
        // currency is current without a round trip, and all this holds is a
        // three-letter code the page already prints in full — there is nothing
        // in it to steal. The cart token next door stays httpOnly, because
        // there very much is. sameSite Lax, so arriving from a search result or
        // an email still carries the choice.
        $this->response->setCookie([
            'name'     => PricingService::CURRENCY_COOKIE,
            'value'    => $code,
            'expire'   => self::COOKIE_DAYS * 86400,
            'path'     => '/',
            'secure'   => $this->request->isSecure(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);

        $dropped = $this->repriceCart($code);

        if ($dropped !== []) {
            // Straight to the basket, whatever the referrer said. A line that
            // has just been removed is a thing to look at, and the basket is
            // the page that shows both the message and the lines that are left;
            // announcing a deletion on a page that renders neither is the same
            // as not announcing it.
            return redirect()->to(locale_url('cart'))
                ->with('error', lang('Commerce.currency.dropped', [$code, implode(', ', $dropped)]));
        }

        return redirect()->to($this->backTo())
            ->with('notice', lang('Commerce.currency.switched', [$code]));
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Re-read an existing basket at the new currency's published prices.
     *
     * @return list<string> the titles of the lines that had no price in it, in
     *                      basket order, for the sentence the visitor gets
     */
    private function repriceCart(string $code): array
    {
        $cart = CartContext::existing();
        if ($cart === null || strtoupper((string) $cart['currency']) === $code) {
            return [];
        }

        $carts = new CartModel();

        // The basket is photographed before the switch and the vanished lines
        // are named from the photograph. Two reasons it has to be this way
        // round: `switchCurrency()` reports what it dropped as bare item ids,
        // which is not something to show a person; and once the row is deleted
        // there is nothing left to read a title from. Keyed by `cart_items.id`,
        // which is the only identifier that cannot collide — a session and a
        // bundle can share an `item_id`.
        $before = array_column($carts->summary((int) $cart['id'])['items'], null, 'id');

        CartContext::switchCurrency($cart, $code);

        $after = array_column($carts->items((int) $cart['id']), null, 'id');

        $dropped = [];
        foreach ($before as $id => $line) {
            if (isset($after[$id])) {
                continue;
            }

            $dropped[] = t_field($line['course_title'] ?? null) ?: lang('Commerce.cart.untitled');
        }

        return $dropped;
    }

    /**
     * Where the visitor was, reduced to a path on this host.
     *
     * The referrer is a header, which means it is whatever the request said it
     * was, and a redirect built straight out of it is an open redirect on the
     * site's own domain. So nothing is taken from it but the path and the query
     * string, and only after the host and port have been matched against ours —
     * exactly, because `learnplus.example.com.evil.net` also ends in our
     * domain. The address that finally goes into the Location header is rebuilt
     * from `base_url()`, so it is ours by construction rather than by
     * inspection.
     */
    private function backTo(): string
    {
        $home = locale_url('');

        $referrer = trim($this->request->getHeaderLine('Referer'));

        // A carriage return in a value that ends up in a Location header is a
        // response-splitting attempt. No web server passes one through in a
        // header, so this can only ever fire on something contrived, and the
        // cost of refusing it is nothing.
        if ($referrer === '' || strpbrk($referrer, "\r\n") !== false) {
            return $home;
        }

        $parts = parse_url($referrer);
        if (! is_array($parts) || ! isset($parts['path'])) {
            return $home;
        }

        $base = parse_url(rtrim(base_url(), '/'));
        if (! is_array($base) || empty($base['host'])) {
            return $home;
        }

        if (isset($parts['host'])) {
            // Named a host, so it has to be this host, on this port — and over
            // a scheme a browser could have loaded a page with. Without the
            // scheme test, "javascript:alert(1)" parses as a bare path and is
            // rebuilt into a real address on our own domain: harmless, but a
            // 404 where the visitor wanted their page back.
            if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)) {
                return $home;
            }
            if (strcasecmp((string) $parts['host'], (string) $base['host']) !== 0) {
                return $home;
            }
            if (($parts['port'] ?? null) !== ($base['port'] ?? null)) {
                return $home;
            }
        } elseif (isset($parts['scheme']) || strncmp((string) $parts['path'], '/', 1) !== 0) {
            // No host: the only shape worth honouring is a rooted path, which
            // cannot leave the site. A scheme without a host, or a path that
            // does not begin at the root, is not a page anybody was on.
            return $home;
        }

        // Collapse the leading slashes. "//elsewhere.example" is a path to
        // parse_url and a protocol-relative URL to a browser, and the two must
        // not be allowed to disagree about which site it means.
        $path = '/' . ltrim((string) $parts['path'], '/');

        // The application need not sit at the root of its host. A path outside
        // its own base belongs to something else on the same domain, and that
        // is not somewhere this controller should be sending anybody.
        $basePath = rtrim((string) ($base['path'] ?? ''), '/');
        if ($basePath !== '' && $path !== $basePath && strpos($path, $basePath . '/') !== 0) {
            return $home;
        }

        $origin = ($base['scheme'] ?? 'https') . '://' . $base['host']
            . (isset($base['port']) ? ':' . $base['port'] : '');

        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

        return $origin . $path . $query;
    }
}
