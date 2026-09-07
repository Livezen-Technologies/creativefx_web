<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

/**
 * Orders.
 *
 * status: pending_payment | paid | partially_refunded | refunded | cancelled | failed
 *
 * An order becomes `paid` in exactly two places: a verified gateway webhook,
 * and an administrator marking a bank transfer received. Never on the browser
 * coming back from a payment page — that redirect can be replayed, forged, or
 * simply never happen because the buyer closed the tab on a train.
 */
class OrderModel extends Model
{
    protected $table         = 'orders';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'order_no', 'user_id', 'account_id', 'status', 'currency', 'country',
        'subtotal_cents', 'discount_cents', 'tax_cents', 'total_cents',
        'coupon_id', 'billing_json', 'gateway', 'notes', 'due_at',
        'placed_at', 'paid_at', 'idempotency_key',
    ];

    /**
     * A human-readable order number: MLP-260907-4F2A.
     *
     * Not the primary key. An id in a URL tells a competitor how many orders
     * you take in a week; and a number a customer reads down the telephone
     * wants a shape, not seven digits.
     */
    public function nextNumber(): string
    {
        do {
            $no = sprintf('MLP-%s-%s', date('ymd'), strtoupper(bin2hex(random_bytes(2))));
        } while ($this->where('order_no', $no)->countAllResults() > 0);

        return $no;
    }

    public function findByNumber(string $orderNo): ?array
    {
        return $this->where('order_no', $orderNo)->first();
    }

    /** @return list<array> */
    public function items(int $orderId): array
    {
        return $this->db->table('order_items')->where('order_id', $orderId)
            ->orderBy('id', 'ASC')->get()->getResultArray();
    }

    /** @return list<array> */
    public function forUser(int $userId, int $limit = 0): array
    {
        return $this->where('user_id', $userId)
            ->whereIn('status', ['paid', 'partially_refunded', 'refunded', 'pending_payment'])
            ->orderBy('id', 'DESC')
            ->findAll($limit ?: null);
    }
}
