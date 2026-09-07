<?php

/**
 * Money, in views.
 *
 * Every function here takes minor units and a currency code together, because a
 * number without its currency is not a price and this site publishes two of
 * them. The division by 100 happens exactly once, at the last possible moment
 * before a human reads it, and never on the way in.
 */

if (! function_exists('money')) {
    /**
     * A price, formatted for the currency it is in.
     *
     * LKR is shown without decimals: prices are set to the nearest hundred
     * rupees, so ".00" on every figure is noise that makes a page harder to
     * scan. USD keeps its cents because a $495.00 course and a $49.50 one are
     * both real.
     */
    function money(?int $cents, string $currency = 'USD'): string
    {
        if ($cents === null) {
            return '';
        }

        $currency = strtoupper($currency);
        $amount   = $cents / 100;

        return match ($currency) {
            'LKR'   => 'Rs ' . number_format($amount, $amount == (int) $amount ? 0 : 2),
            'USD'   => '$' . number_format($amount, 2),
            default => $currency . ' ' . number_format($amount, 2),
        };
    }
}

if (! function_exists('current_currency')) {
    /**
     * The currency this visitor is being priced in.
     *
     * Resolved once per request and remembered, because it is asked for by
     * every card on a catalogue page and the answer cannot change half way
     * down.
     */
    function current_currency(): string
    {
        static $resolved = null;

        return $resolved ??= (new \Modules\Commerce\Services\PricingService())->resolveCurrency();
    }
}

if (! function_exists('cart_count')) {
    /** How many seats are in the basket, for the header badge. */
    function cart_count(): int
    {
        static $count = null;
        if ($count !== null) {
            return $count;
        }

        $cart = \Modules\Commerce\Libraries\CartContext::existing();

        return $count = $cart === null
            ? 0
            : (new \Modules\Commerce\Models\CartModel())->summary((int) $cart['id'])['count'];
    }
}

if (! function_exists('currency_options')) {
    /**
     * The currencies a visitor may switch to.
     *
     * @return list<array{code:string, symbol:string, name:string}>
     */
    function currency_options(): array
    {
        static $rows = null;

        return $rows ??= db_connect()->table('currencies')
            ->select('code, symbol, name')
            ->where('is_active', 1)->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
    }
}
