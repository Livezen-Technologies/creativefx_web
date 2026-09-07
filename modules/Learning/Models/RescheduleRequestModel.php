<?php

namespace Modules\Learning\Models;

use CodeIgniter\Model;

/**
 * Transfers between dates.
 *
 * The policy is a promise on the policies page, so it is built rather than only
 * written: free at ten or more business days' notice, a fee inside that. A
 * policy that exists only as prose is applied differently by whoever happens to
 * answer the email, which is how a training school ends up with two customers
 * who were told two different things.
 */
class RescheduleRequestModel extends Model
{
    protected $table         = 'reschedule_requests';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'enrolment_id', 'from_session_id', 'to_session_id', 'status',
        'fee_cents', 'currency', 'reason', 'decision_note',
        'requested_at', 'decided_at', 'decided_by',
    ];

    /** Free at this much notice or more. */
    public const FREE_NOTICE_BUSINESS_DAYS = 10;

    /**
     * Business days between now and a date, counting Monday to Friday.
     *
     * Public holidays are not in this count. Sri Lanka has a lot of them and
     * they move; getting that wrong in the learner's favour is the safe
     * direction, and the admin can waive a fee in the one case a year where it
     * matters.
     */
    public static function businessDaysUntil(string $date): int
    {
        $start = new \DateTimeImmutable('today');
        $end   = new \DateTimeImmutable($date);
        if ($end <= $start) {
            return 0;
        }

        $days = 0;
        for ($d = $start; $d < $end; $d = $d->modify('+1 day')) {
            if ((int) $d->format('N') <= 5) {
                $days++;
            }
        }

        return $days;
    }

    /** Whether a transfer away from this date is free. */
    public static function isFree(?string $startDate): bool
    {
        if (empty($startDate)) {
            return true;   // self-paced: nothing to move away from
        }

        return self::businessDaysUntil($startDate) >= self::FREE_NOTICE_BUSINESS_DAYS;
    }
}
