<?php

namespace Modules\Commerce\Services;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * What a thing costs, to this visitor, today.
 *
 * Two rules decide everything here, and both are the opposite of what a
 * multi-currency site usually does:
 *
 *   **Prices are published, not converted.** There is no exchange rate in this
 *   class. The LKR price of a course is a number a human chose and can defend;
 *   it is not the USD price through this morning's rate. Runtime conversion
 *   gives you LKR 148,237 on a page, a margin that moves while you sleep, and a
 *   price that changes between the course page and the checkout. The reference
 *   rate of LKR 350 = USD 1 exists for *reporting* — turning two currencies of
 *   revenue into one number — and is never used to price anything.
 *
 *   **The currency is decided once and frozen.** It is resolved when a cart is
 *   created, written onto the cart, and never re-derived. A visitor whose
 *   currency is recomputed per request is a visitor whose price changes because
 *   a CDN header wobbled between two page loads.
 *
 * Resolution order for the currency, most trusted first:
 *
 *   1. an explicit choice the visitor made, in the `mlp_currency` cookie
 *   2. `CF-IPCountry`, which Cloudflare sets in front of this site
 *   3. the country on the signed-in user's profile
 *   4. `Accept-Language`'s region subtag, as a last guess
 *   5. the default price book
 *
 * A country maps to a price book; a price book names a currency. Adding a GBP
 * or AED book later is a row in `price_books`, not a deployment.
 */
class PricingService
{
    /**
     * For reporting only — LKR per USD, the convention already used across the
     * group's contracts. Never call this to price anything; see the class
     * comment. It exists so a revenue report can show one total.
     */
    public const REPORTING_LKR_PER_USD = 350;

    public const CURRENCY_COOKIE = 'mlp_currency';

    private BaseConnection $db;

    /** @var array<string, array>|null price books, by code, loaded once */
    private ?array $books = null;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    // ── Which currency ──────────────────────────────────────────────────────

    /**
     * The country this visitor should be priced as being in, as an ISO-3166
     * alpha-2 code, or null if nothing said.
     */
    public function resolveCountry(?IncomingRequest $request = null, ?array $user = null): ?string
    {
        $request ??= service('request');

        // Cloudflare sits in front of this site and adds the header on every
        // request. It is the only signal here that is derived from the
        // connection rather than from something the browser volunteered.
        $cf = $request->getHeaderLine('CF-IPCountry');
        if ($cf !== '' && preg_match('/^[A-Z]{2}$/', $cf) && $cf !== 'XX' && $cf !== 'T1') {
            return $cf;
        }

        if (! empty($user['country']) && preg_match('/^[A-Za-z]{2}$/', (string) $user['country'])) {
            return strtoupper((string) $user['country']);
        }

        // en-LK, si-LK, en-GB. A language alone (plain "en") says nothing about
        // where somebody is, so only a region subtag counts.
        if (preg_match('/[a-z]{2}[-_]([A-Z]{2})\b/', $request->getHeaderLine('Accept-Language'), $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * The currency to price in. Honours an explicit choice above every guess:
     * somebody who has clicked "USD" has told us something no header can.
     */
    public function resolveCurrency(?IncomingRequest $request = null, ?array $user = null): string
    {
        $request ??= service('request');

        $chosen = strtoupper((string) ($request->getCookie(self::CURRENCY_COOKIE) ?? ''));
        if ($chosen !== '' && $this->isActiveCurrency($chosen)) {
            return $chosen;
        }

        return $this->bookFor($this->resolveCountry($request, $user))['currency'];
    }

    /**
     * The price book a country falls into, or the default one.
     *
     * @return array{id:int, code:string, currency:string, country_codes_json:?string, is_default:int}
     */
    public function bookFor(?string $country): array
    {
        $books = $this->books();

        if ($country !== null) {
            $country = strtoupper($country);
            foreach ($books as $book) {
                $codes = json_decode((string) ($book['country_codes_json'] ?? '[]'), true) ?: [];
                if (in_array($country, array_map('strtoupper', $codes), true)) {
                    return $book;
                }
            }
        }

        foreach ($books as $book) {
            if ((int) $book['is_default'] === 1) {
                return $book;
            }
        }

        // Nothing configured at all. Rather than throw on a page render, fall
        // back to a book-shaped array so the site still prices in USD — a
        // missing seed must not take the catalogue down.
        return ['id' => 0, 'code' => 'global', 'currency' => 'USD', 'country_codes_json' => null, 'is_default' => 1];
    }

    // ── What a session costs ────────────────────────────────────────────────

    /**
     * The list price of a session in a currency, in minor units, or null when
     * no price has been published in that currency.
     *
     * Null is deliberately not "fall back to the USD number": a course with no
     * LKR price is a course that has not been priced for Sri Lanka, and showing
     * a converted figure would be exactly the runtime conversion this class
     * exists to avoid. The page shows the other currency's price, labelled,
     * instead of inventing one.
     *
     * @return array{price_cents:int, compare_at_cents:?int, currency:string}|null
     */
    public function sessionPrice(int $sessionId, string $currency): ?array
    {
        $row = $this->db->table('session_prices')
            ->select('price_cents, compare_at_cents, currency')
            ->where('session_id', $sessionId)->where('currency', $currency)
            ->get()->getRowArray();

        if ($row === null) {
            return null;
        }

        return [
            'price_cents'      => (int) $row['price_cents'],
            'compare_at_cents' => $row['compare_at_cents'] === null ? null : (int) $row['compare_at_cents'],
            'currency'         => (string) $row['currency'],
        ];
    }

    /**
     * The cheapest published price across a course's on-sale sessions, per
     * mode. This is what the sticky booking panel shows before a date is
     * chosen, and what the catalogue card prints as "from".
     *
     * @return array<string, array{price_cents:int, compare_at_cents:?int, currency:string, session_id:int}>
     */
    public function courseFromPrices(int $courseId, string $currency): array
    {
        $rows = $this->db->table('course_sessions cs')
            ->select('cs.id, cs.mode, sp.price_cents, sp.compare_at_cents')
            ->join('session_prices sp', 'sp.session_id = cs.id AND sp.currency = ' . $this->db->escape($currency), 'inner', false)
            ->where('cs.course_id', $courseId)
            ->where('cs.is_private', 0)
            ->whereIn('cs.status', ['open', 'confirmed', 'waitlist'])
            ->orderBy('sp.price_cents', 'ASC')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            // Ordered ascending, so the first row seen for a mode is its
            // cheapest and later ones are ignored.
            if (isset($out[$row['mode']])) {
                continue;
            }
            $out[$row['mode']] = [
                'price_cents'      => (int) $row['price_cents'],
                'compare_at_cents' => $row['compare_at_cents'] === null ? null : (int) $row['compare_at_cents'],
                'currency'         => $currency,
                'session_id'       => (int) $row['id'],
            ];
        }

        return $out;
    }

    public function bundlePrice(int $bundleId, string $currency): ?array
    {
        $row = $this->db->table('bundle_prices')
            ->select('price_cents, compare_at_cents')
            ->where('bundle_id', $bundleId)->where('currency', $currency)
            ->get()->getRowArray();

        return $row === null ? null : [
            'price_cents'      => (int) $row['price_cents'],
            'compare_at_cents' => $row['compare_at_cents'] === null ? null : (int) $row['compare_at_cents'],
            'currency'         => $currency,
        ];
    }

    // ── Discounts ───────────────────────────────────────────────────────────

    /**
     * Every automatic discount that applies to one line, worked out in minor
     * units. Rules do not stack blindly: they are sorted by priority and the
     * single best one wins, because a group booking made three months early
     * should get the better of the two discounts, not both.
     *
     * @param array{session:array, qty:int, unit_price_cents:int, currency:string, user:?array, account:?array} $ctx
     * @return array{discount_cents:int, label:string, kind:string}
     */
    public function autoDiscount(array $ctx): array
    {
        $best = ['discount_cents' => 0, 'label' => '', 'kind' => ''];

        $rules = $this->db->table('discount_rules')
            ->where('is_active', 1)->orderBy('priority', 'DESC')->get()->getResultArray();

        foreach ($rules as $rule) {
            $conditions = json_decode((string) $rule['conditions_json'], true) ?: [];
            $value      = json_decode((string) $rule['value_json'], true) ?: [];

            if (! $this->ruleApplies((string) $rule['kind'], $conditions, $ctx)) {
                continue;
            }

            $line     = $ctx['unit_price_cents'] * max(1, $ctx['qty']);
            $discount = ($value['type'] ?? 'percent') === 'percent'
                ? (int) floor($line * ((int) ($value['amount'] ?? 0)) / 100)
                : (int) ($value['amount_cents'][$ctx['currency']] ?? 0);

            if ($discount > $best['discount_cents']) {
                $best = [
                    'discount_cents' => $discount,
                    'label'          => (string) $rule['name'],
                    'kind'           => (string) $rule['kind'],
                ];
            }
        }

        return $best;
    }

    /**
     * Whether one rule's conditions are met.
     *
     * Kept as a flat match rather than a rules engine: there are five kinds,
     * they are named in the blueprint, and a general expression evaluator here
     * would be a second language nobody can debug in production.
     */
    private function ruleApplies(string $kind, array $conditions, array $ctx): bool
    {
        switch ($kind) {
            case 'early_bird':
                $start = $ctx['session']['start_date'] ?? null;
                if (empty($start)) {
                    return false;   // self-paced has no date to be early for
                }
                $days = (int) ($conditions['days_before'] ?? 30);

                return strtotime($start) - time() >= $days * 86400;

            case 'group':
                return $ctx['qty'] >= (int) ($conditions['min_seats'] ?? 3);

            case 'alumni':
                if (empty($ctx['user']['id'])) {
                    return false;
                }

                return (int) ($this->db->table('enrolments')
                    ->where('user_id', (int) $ctx['user']['id'])
                    ->whereIn('status', ['completed', 'active'])
                    ->countAllResults()) > 0;

            case 'account':
                return ! empty($ctx['account']['id']);

            case 'bundle':
                return ($ctx['item_type'] ?? 'session') === 'bundle';
        }

        return false;
    }

    /**
     * Validate a typed coupon against a cart total.
     *
     * @return array{ok:bool, reason:string, discount_cents:int, coupon:?array}
     */
    public function applyCoupon(string $code, int $subtotalCents, string $currency, ?int $userId = null): array
    {
        $fail = static fn (string $why): array => ['ok' => false, 'reason' => $why, 'discount_cents' => 0, 'coupon' => null];

        $coupon = $this->db->table('coupons')->where('code', strtoupper(trim($code)))->get()->getRowArray();
        if ($coupon === null || (int) $coupon['is_active'] !== 1) {
            return $fail('unknown');
        }

        $now = date('Y-m-d H:i:s');
        if (! empty($coupon['starts_at']) && $coupon['starts_at'] > $now) {
            return $fail('not_yet');
        }
        if (! empty($coupon['ends_at']) && $coupon['ends_at'] < $now) {
            return $fail('expired');
        }
        if ($coupon['max_uses'] !== null && (int) $coupon['used_count'] >= (int) $coupon['max_uses']) {
            return $fail('used_up');
        }
        // A fixed-amount coupon is denominated in one currency. Offering "LKR
        // 5,000 off" against a USD cart would be a 5,000-dollar discount.
        if ($coupon['type'] === 'fixed' && strtoupper((string) $coupon['currency']) !== strtoupper($currency)) {
            return $fail('wrong_currency');
        }
        if ($subtotalCents < (int) $coupon['min_spend_cents']) {
            return $fail('min_spend');
        }
        if ($coupon['per_user_limit'] !== null && $userId !== null) {
            $used = $this->db->table('orders')
                ->where('coupon_id', (int) $coupon['id'])->where('user_id', $userId)
                ->whereIn('status', ['paid', 'partially_refunded'])
                ->countAllResults();
            if ($used >= (int) $coupon['per_user_limit']) {
                return $fail('per_user');
            }
        }

        $discount = $coupon['type'] === 'percent'
            ? (int) floor($subtotalCents * (int) $coupon['value'] / 100)
            : (int) $coupon['value'];

        return [
            'ok'             => true,
            'reason'         => 'ok',
            // Never more than the cart is worth: a coupon must not create a
            // negative total that the gateway then refuses in a way nobody
            // understands.
            'discount_cents' => min($discount, $subtotalCents),
            'coupon'         => $coupon,
        ];
    }

    // ── Tax ─────────────────────────────────────────────────────────────────

    /**
     * Tax on an amount, in minor units, with the label to print on the invoice.
     *
     * Stored per order item at the rate that applied on the day — see the
     * migration — so this is only ever called while an order is being built,
     * never when one is displayed.
     *
     * @return array{tax_cents:int, label:string, rate_bp:int}
     */
    public function tax(int $amountCents, ?string $country): array
    {
        $none = ['tax_cents' => 0, 'label' => '', 'rate_bp' => 0];
        if ($country === null) {
            return $none;
        }

        $rule = $this->db->table('tax_rules')
            ->where('country', strtoupper($country))->where('is_active', 1)
            ->orderBy('id', 'DESC')->get()->getRowArray();

        if ($rule === null) {
            return $none;
        }

        $rateBp = (int) $rule['rate_bp'];

        return [
            // Basis points, so the arithmetic never leaves the integers.
            'tax_cents' => (int) round($amountCents * $rateBp / 10000),
            'label'     => (string) $rule['label'],
            'rate_bp'   => $rateBp,
        ];
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /** @return list<array> */
    private function books(): array
    {
        return $this->books ??= $this->db->table('price_books')
            ->where('is_active', 1)->orderBy('sort_order', 'ASC')->get()->getResultArray();
    }

    private function isActiveCurrency(string $code): bool
    {
        return $this->db->table('currencies')
            ->where('code', $code)->where('is_active', 1)->countAllResults() > 0;
    }
}
