<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

/**
 * Discount codes somebody types.
 *
 * Validation lives in PricingService::applyCoupon(), not here, because a coupon
 * is only valid *against a particular cart* — the minimum spend, the currency,
 * the per-customer limit and the expiry are all questions about the purchase
 * rather than about the row. This model stores and counts.
 */
class CouponModel extends Model
{
    protected $table         = 'coupons';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code', 'type', 'value', 'currency', 'min_spend_cents', 'max_uses',
        'used_count', 'per_user_limit', 'starts_at', 'ends_at',
        'applies_to_json', 'is_active',
    ];

    /** Codes are stored and compared upper case, so typing is not a lottery. */
    protected function normalise(array $data): array
    {
        if (isset($data['data']['code'])) {
            $data['data']['code'] = strtoupper(trim((string) $data['data']['code']));
        }

        return $data;
    }

    protected $beforeInsert = ['normalise'];
    protected $beforeUpdate = ['normalise'];

    /**
     * Count a use.
     *
     * Incremented by the database rather than read-and-written in PHP, so two
     * orders placed in the same second cannot both read the same starting value
     * and let a single-use code be redeemed twice.
     */
    public function countUse(int $couponId): void
    {
        $this->db->query('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$couponId]);
    }
}
