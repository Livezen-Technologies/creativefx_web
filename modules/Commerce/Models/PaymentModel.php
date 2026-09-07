<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

/**
 * What a gateway told us, kept verbatim.
 *
 * The unique key on (gateway, gateway_ref) is the whole idempotency story for
 * webhooks. Gateways redeliver — sometimes for days, sometimes in a burst when
 * their own queue drains — and the second delivery must be a no-op rather than
 * a second enrolment and a second confirmation email.
 */
class PaymentModel extends Model
{
    protected $table         = 'payments';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';
    protected $allowedFields = [
        'order_id', 'gateway', 'gateway_ref', 'status', 'amount_cents',
        'currency', 'raw_payload_json', 'received_at',
    ];

    public function findByRef(string $gateway, string $ref): ?array
    {
        return $this->where('gateway', $gateway)->where('gateway_ref', $ref)->first();
    }

    /**
     * Record a payment exactly once.
     *
     * Returns null when this reference has already been seen, which is the
     * caller's signal to stop rather than to fulfil the order again.
     */
    public function recordOnce(array $data): ?array
    {
        if ($this->findByRef((string) $data['gateway'], (string) $data['gateway_ref']) !== null) {
            return null;
        }

        $id = $this->insert($data, true);

        return $this->find($id);
    }
}
