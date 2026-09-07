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

        // LKR and USD are named rather than derived, deliberately. Their
        // rendering is what every price on this site already looks like and
        // what the pricing checks assert; deriving them would be a tidier
        // function and a silent change to three hundred pages.
        if ($currency === 'LKR') {
            return 'Rs ' . number_format($amount, $amount == (int) $amount ? 0 : 2);
        }
        if ($currency === 'USD') {
            return '$' . number_format($amount, 2);
        }

        // Everything else comes from the `currencies` table, which carries a
        // symbol and a decimal count for exactly this purpose and which this
        // function ignored — so a seeded "₹" was never printed and INR came out
        // as "INR 1,250.00". Adding each new currency to a match here is how
        // that repeats for the one after it.
        $meta   = currency_meta($currency);
        $dp     = $amount == (int) $amount ? 0 : (int) $meta['decimals'];
        $figure = number_format($amount, $dp);

        // A glyph sits against the number; letters need the space between.
        // "₹1,250" and "AED 55" are both right; "₹ 1,250" and "AED55" are not.
        return preg_match('/^\p{L}+$/u', $meta['symbol']) === 1
            ? $meta['symbol'] . ' ' . $figure
            : $meta['symbol'] . $figure;
    }
}

if (! function_exists('currency_meta')) {
    /**
     * A currency's symbol and decimal count, from the table that holds them.
     *
     * Falls back to the code as its own symbol, which is what an unknown
     * currency should read as: "XYZ 1,250" is honest about not knowing, and a
     * guessed symbol is not.
     *
     * @return array{symbol:string, decimals:int}
     */
    function currency_meta(string $code): array
    {
        static $rows = null;

        if ($rows === null) {
            $rows = [];

            try {
                foreach (db_connect()->table('currencies')->get()->getResultArray() as $row) {
                    $rows[strtoupper((string) $row['code'])] = [
                        'symbol'   => trim((string) $row['symbol']) ?: strtoupper((string) $row['code']),
                        'decimals' => (int) ($row['decimals'] ?? 2),
                    ];
                }
            } catch (Throwable) {
                // Money is rendered in emails and in CLI output too, where the
                // table may not be reachable. A price is worth printing without
                // its symbol; it is not worth an exception on an invoice.
                $rows = [];
            }
        }

        return $rows[strtoupper($code)] ?? ['symbol' => strtoupper($code), 'decimals' => 2];
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
