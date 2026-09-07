<?php

namespace Modules\Commerce\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The commercial ground rules: which currencies exist, which country buys in
 * which of them, what tax applies, and the automatic discounts.
 *
 * Idempotence, following the house rule: list tables seed only while empty, so
 * a release can never undo somebody's work. If an administrator has added a
 * price book for the Gulf or changed the early-bird window, running this again
 * leaves it alone.
 *
 * There are no coupon codes seeded. A coupon is a marketing decision with a
 * budget attached; inventing one here would mean shipping a discount nobody
 * agreed to.
 */
class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        $this->currencies();
        $this->priceBooks();
        $this->taxRules();
        $this->discountRules();
    }

    private function currencies(): void
    {
        if ($this->db->table('currencies')->countAllResults() > 0) {
            return;
        }

        $this->db->table('currencies')->insertBatch([
            [
                'code'     => 'USD',
                'name'     => 'US Dollar',
                'symbol'   => '$',
                'decimals' => 2,
                // Prices are set to whole dollars, so the rounding granularity
                // after a percentage discount is a dollar rather than a cent.
                // Nobody publishes a course at $494.37.
                'minor_step' => 100,
                'is_active'  => 1,
                'sort_order' => 1,
            ],
            [
                'code'     => 'LKR',
                'name'     => 'Sri Lankan Rupee',
                'symbol'   => 'Rs',
                'decimals' => 2,
                // A hundred rupees. An LKR price ending in 37 rupees reads as a
                // conversion, which is exactly the impression this site is
                // built to avoid.
                'minor_step' => 10000,
                'is_active'  => 1,
                'sort_order' => 2,
            ],
        ]);
    }

    private function priceBooks(): void
    {
        if ($this->db->table('price_books')->countAllResults() > 0) {
            return;
        }

        $this->db->table('price_books')->insertBatch([
            [
                'code'               => 'lk',
                'name'               => 'Sri Lanka',
                'currency'           => 'LKR',
                'country_codes_json' => json_encode(['LK']),
                'is_default'         => 0,
                'is_active'          => 1,
                'sort_order'         => 1,
            ],
            [
                // Everything that is not Sri Lanka. Listed last and marked
                // default, so a country nobody has thought about lands here
                // rather than falling through to no price at all.
                'code'               => 'global',
                'name'               => 'Global (USD)',
                'currency'           => 'USD',
                'country_codes_json' => json_encode([]),
                'is_default'         => 1,
                'is_active'          => 1,
                'sort_order'         => 9,
            ],
        ]);
    }

    private function taxRules(): void
    {
        if ($this->db->table('tax_rules')->countAllResults() > 0) {
            return;
        }

        // Seeded inactive, at zero, on purpose. Whether Sri Lankan VAT applies
        // to training services, and at what rate, is a question for the
        // school's accountant and not for this file — but the rule has to
        // exist, with the right shape, so that turning it on is a number in the
        // admin rather than a migration. Charging the wrong tax is worse than
        // charging none and correcting it before launch.
        $this->db->table('tax_rules')->insert([
            'country'         => 'LK',
            'region'          => null,
            'rate_bp'         => 0,
            'label'           => 'VAT',
            'applies_to_json' => json_encode(['session', 'bundle']),
            'is_active'       => 0,
        ]);
    }

    private function discountRules(): void
    {
        if ($this->db->table('discount_rules')->countAllResults() > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $this->db->table('discount_rules')->insertBatch([
            [
                'name'            => 'Early bird — 30 days',
                'kind'            => 'early_bird',
                'conditions_json' => json_encode(['days_before' => 30]),
                'value_json'      => json_encode(['type' => 'percent', 'amount' => 10]),
                // Priority orders the rules; only the single best one applies,
                // so a group booking made early gets the better discount rather
                // than both. Stacking discounts is how a margin disappears
                // without anybody deciding that it should.
                'priority'        => 10,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'Group — 3 or more seats',
                'kind'            => 'group',
                'conditions_json' => json_encode(['min_seats' => 3]),
                'value_json'      => json_encode(['type' => 'percent', 'amount' => 15]),
                'priority'        => 20,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            [
                'name'            => 'Returning learner',
                'kind'            => 'alumni',
                'conditions_json' => json_encode([]),
                'value_json'      => json_encode(['type' => 'percent', 'amount' => 10]),
                'priority'        => 5,
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
        ]);
    }
}
