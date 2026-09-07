<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

class MembershipPlanModel extends Model
{
    protected $table         = 'membership_plans';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['code', 'months', 'name', 'summary', 'sort_order', 'status'];

    /**
     * The plans on sale, priced in one currency, cheapest term first.
     *
     * Joined rather than queried per plan: four plans is four queries on a page
     * whose whole job is to be compared at a glance, and the same shape as
     * CardPricer's decorate() for the catalogue.
     *
     * A plan with no price in this currency is **left out**, not shown at zero.
     * A missing row is a plan somebody forgot to price, and the honest response
     * to that is not to offer a year of the library for nothing.
     *
     * @return list<array>
     */
    public function published(string $currency): array
    {
        $rows = $this->select('membership_plans.*, p.price_cents, p.compare_at_cents')
            ->join('membership_plan_prices p', 'p.plan_id = membership_plans.id AND p.currency = ' . $this->db->escape($currency), 'inner')
            ->where('membership_plans.status', 'published')
            ->orderBy('membership_plans.sort_order', 'ASC')
            ->findAll();

        foreach ($rows as &$row) {
            $row['price_cents']      = (int) $row['price_cents'];
            $row['compare_at_cents'] = $row['compare_at_cents'] === null ? null : (int) $row['compare_at_cents'];
            $row['currency']         = $currency;

            // What a month works out at on this plan — the only figure that
            // makes four different spans comparable at a glance.
            //
            // Rounded to whole currency units, not to the cent. LKR prices on
            // this site are set to the nearest hundred rupees, and "Rs 3,833.33
            // a month" reads exactly like the runtime conversion the whole
            // pricing model refuses to do. It is a comparison rather than a
            // price anybody is charged, so the rounding costs nothing.
            $perMonth = $row['price_cents'] / max(1, (int) $row['months']);
            $row['per_month_cents'] = (int) (round($perMonth / 100) * 100);
        }

        return $rows;
    }

    /** One published plan, priced, or null. */
    public function priced(int $planId, string $currency): ?array
    {
        foreach ($this->published($currency) as $plan) {
            if ((int) $plan['id'] === $planId) {
                return $plan;
            }
        }

        return null;
    }
}
