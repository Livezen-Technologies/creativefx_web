<?php

namespace Modules\Commerce\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The membership plans, and the two currencies they added.
 *
 * **Upserts by code; it does not guard on an empty table.** CommerceSeeder ends
 * every list with `if (count > 0) return`, which is right for a first install
 * and useless here: the live database already has USD and LKR, so a whole-table
 * guard would skip the very rows this exists to add and report success. That
 * failure is silent, which is how the previous brand's settings survived three
 * deploys. Each row here is added only if its code is absent, and an existing
 * row is left exactly as an administrator left it.
 *
 * Prices are published, never converted — the rule PricingService is built
 * around. The four LKR figures are the school's; the USD, INR and AED ones were
 * chosen against the LKR 350/USD convention already used for reporting and then
 * rounded to prices somebody would actually print:
 *
 *   term        LKR      USD      INR      AED
 *   1 month     4,500     15    1,250       55
 *   3 months   11,500     35    2,950      129
 *   6 months   17,500     55    4,650      199
 *   1 year     30,000     89    7,500      329
 *
 * The discounts are real and increasing — a year costs 5.6 months at the
 * monthly rate — which is the whole argument for the longer terms and the
 * reason `compare_at_cents` carries what the same period would cost monthly.
 */
class MembershipSeeder extends Seeder
{
    /**
     * Minor units, in the order the plans are declared below.
     *
     * @var array<string, list<int>>
     */
    private const PRICES = [
        'LKR' => [450000, 1150000, 1750000, 3000000],
        'USD' => [1500, 3500, 5500, 8900],
        'INR' => [125000, 295000, 465000, 750000],
        'AED' => [5500, 12900, 19900, 32900],
    ];

    public function run(): void
    {
        $this->currencies();
        $this->priceBooks();
        $this->plans();
    }

    private function currencies(): void
    {
        $rows = [
            [
                'code'     => 'INR',
                'name'     => 'Indian Rupee',
                'symbol'   => '₹',
                'decimals' => 2,
                // ₹50. The published prices are all multiples of it, so a
                // percentage discount rounds to something printable rather
                // than to ₹2,952.50.
                'minor_step' => 5000,
                'is_active'  => 1,
                'sort_order' => 3,
            ],
            [
                'code'       => 'AED',
                'name'       => 'UAE Dirham',
                'symbol'     => 'AED',
                'decimals'   => 2,
                'minor_step' => 100,
                'is_active'  => 1,
                'sort_order' => 4,
            ],
        ];

        foreach ($rows as $row) {
            if ($this->db->table('currencies')->where('code', $row['code'])->countAllResults() === 0) {
                $this->db->table('currencies')->insert($row);
            }
        }
    }

    private function priceBooks(): void
    {
        $rows = [
            [
                'code'               => 'in',
                'name'               => 'India',
                'currency'           => 'INR',
                'country_codes_json' => json_encode(['IN']),
                'is_default'         => 0,
                'is_active'          => 1,
                'sort_order'         => 2,
            ],
            [
                // The Emirates only. The rest of the Gulf is not priced in
                // dirhams, and mapping it here would show a Saudi buyer a
                // currency they do not spend.
                'code'               => 'ae',
                'name'               => 'United Arab Emirates',
                'currency'           => 'AED',
                'country_codes_json' => json_encode(['AE']),
                'is_default'         => 0,
                'is_active'          => 1,
                'sort_order'         => 3,
            ],
        ];

        foreach ($rows as $row) {
            if ($this->db->table('price_books')->where('code', $row['code'])->countAllResults() === 0) {
                $this->db->table('price_books')->insert($row);
            }
        }
    }

    private function plans(): void
    {
        helper('norlanka');
        $now = date('Y-m-d H:i:s');

        $plans = [
            ['code' => 'm1',  'months' => 1,  'name' => 'Monthly',   'summary' => 'One month of the whole self-paced library. Cancel by simply not renewing.'],
            ['code' => 'm3',  'months' => 3,  'name' => 'Quarterly', 'summary' => 'Three months, at a lower monthly rate than paying by the month.'],
            ['code' => 'm6',  'months' => 6,  'name' => 'Half year',  'summary' => 'Six months — long enough to finish a certificate track at a working pace.'],
            ['code' => 'm12', 'months' => 12, 'name' => 'Annual',    'summary' => 'A full year, and the lowest monthly rate we publish.'],
        ];

        foreach ($plans as $i => $plan) {
            $existing = $this->db->table('membership_plans')->where('code', $plan['code'])->get()->getRowArray();

            if ($existing === null) {
                $this->db->table('membership_plans')->insert([
                    'code'       => $plan['code'],
                    'months'     => $plan['months'],
                    'name'       => json_encode(content_locales(['en' => $plan['name']]), JSON_UNESCAPED_UNICODE),
                    'summary'    => json_encode(content_locales(['en' => $plan['summary']]), JSON_UNESCAPED_UNICODE),
                    'sort_order' => $i + 1,
                    'status'     => 'published',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $planId = (int) $this->db->insertID();
            } else {
                $planId = (int) $existing['id'];
            }

            foreach (self::PRICES as $currency => $amounts) {
                $has = $this->db->table('membership_plan_prices')
                    ->where('plan_id', $planId)->where('currency', $currency)
                    ->countAllResults();

                if ($has > 0) {
                    continue;
                }

                // What the same span would cost bought a month at a time, so
                // the saving on the longer terms is shown rather than asserted.
                // Null on the monthly plan: there is nothing to compare it to,
                // and a struck-through price equal to the real one is a lie.
                $compare = $plan['months'] > 1
                    ? self::PRICES[$currency][0] * $plan['months']
                    : null;

                $this->db->table('membership_plan_prices')->insert([
                    'plan_id'          => $planId,
                    'currency'         => $currency,
                    'price_cents'      => $amounts[$i],
                    'compare_at_cents' => $compare,
                ]);
            }
        }
    }
}
