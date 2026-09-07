<?php

namespace Modules\Commerce\Libraries;

use Modules\Commerce\Models\CartModel;
use Modules\Commerce\Services\PricingService;

/**
 * Finding, or starting, this visitor's basket.
 *
 * Two entry points on purpose:
 *
 *   `existing()` never writes. It is what the header badge and any read-only
 *   page calls, so that a crawler walking the catalogue does not leave a
 *   million empty carts and a Set-Cookie header on every page of the site.
 *
 *   `current()` creates one if there is none. Only the routes that actually put
 *   something in a basket call it.
 *
 * Getting that the wrong way round is the classic version of this bug: a cart
 * created on first page view means every response varies by cookie, which
 * quietly makes the whole catalogue uncacheable at the CDN.
 */
class CartContext
{
    public const COOKIE = 'mlp_cart';

    /** Two weeks, matching the cart's own expiry. */
    private const COOKIE_DAYS = 14;

    private static ?array $cache = null;

    /** The cart this visitor already has, or null. Never writes. */
    public static function existing(): ?array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $token = (string) (service('request')->getCookie(self::COOKIE) ?? '');
        if ($token === '') {
            return null;
        }

        return self::$cache = (new CartModel())->findByToken($token);
    }

    /** The cart, started if this is the first thing going into it. */
    public static function current(): array
    {
        $cart = self::existing();
        if ($cart !== null) {
            return $cart;
        }

        $pricing  = new PricingService();
        $request  = service('request');
        $country  = $pricing->resolveCountry($request);
        $currency = $pricing->resolveCurrency($request);

        $cart = (new CartModel())->start($currency, $country, \Modules\Account\Libraries\LearnerAuth::id());

        // httpOnly, so no script on the page can read the token; sameSite Lax,
        // so it still survives arriving from a search result or an email but is
        // not sent on a cross-site POST; secure whenever the site is on HTTPS.
        service('response')->setCookie([
            'name'     => self::COOKIE,
            'value'    => $cart['token'],
            'expire'   => self::COOKIE_DAYS * 86400,
            'path'     => '/',
            'secure'   => $request->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return self::$cache = $cart;
    }

    /**
     * Reprice an existing cart into a different currency.
     *
     * Everything in it has to be re-read at the new currency's published price
     * — not converted — and anything that has no price in that currency is
     * dropped with a message rather than silently left at the old number.
     *
     * @return array{cart:array, dropped:list<string>}
     */
    public static function switchCurrency(array $cart, string $currency): array
    {
        $pricing = new PricingService();
        $carts   = new CartModel();
        $db      = db_connect();

        $dropped = [];

        foreach ($carts->items((int) $cart['id']) as $item) {
            $price = $item['item_type'] === 'bundle'
                ? $pricing->bundlePrice((int) $item['item_id'], $currency)
                : $pricing->sessionPrice((int) $item['item_id'], $currency);

            if ($price === null) {
                $dropped[] = (string) $item['item_id'];
                $db->table('cart_items')->where('id', (int) $item['id'])->delete();

                continue;
            }

            $db->table('cart_items')->where('id', (int) $item['id'])
                ->update(['unit_price_cents' => $price['price_cents']]);
        }

        $carts->update((int) $cart['id'], ['currency' => $currency]);
        self::$cache = $carts->find((int) $cart['id']);

        return ['cart' => self::$cache, 'dropped' => $dropped];
    }

    /** Forget the cached row — after a change that this request will read back. */
    public static function forget(): void
    {
        self::$cache = null;
    }
}
