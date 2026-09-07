<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

class MembershipModel extends Model
{
    protected $table         = 'memberships';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'plan_id', 'order_item_id', 'status', 'starts_at', 'expires_at'];

    /**
     * This learner's live pass, or null.
     *
     * The single definition of "is a member", asked on every gated page. Both
     * halves matter: a cancelled pass is not live, and neither is one whose
     * term has run out — and `status` alone would say otherwise for ever,
     * because nothing sweeps expired rows. Reading the date is what makes a
     * pass end without a cron job having to be reliable.
     *
     * The one with the furthest expiry wins, so somebody who renewed early
     * holds the longer of the two rather than whichever was written last.
     */
    public function activeFor(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        return $this->where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('expires_at', 'DESC')
            ->first();
    }

    /** @return list<array> every pass this learner has held, newest first */
    public function historyFor(int $userId): array
    {
        return $this->select('memberships.*, p.name AS plan_name, p.months')
            ->join('membership_plans p', 'p.id = memberships.plan_id', 'left')
            ->where('memberships.user_id', $userId)
            ->orderBy('memberships.starts_at', 'DESC')
            ->findAll();
    }
}
