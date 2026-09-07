<?php

namespace Modules\Learning\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table         = 'attendance';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['enrolment_id', 'session_day_id', 'status', 'minutes', 'marked_by'];

    /** Mark one learner on one day, replacing whatever was recorded before. */
    public function mark(int $enrolmentId, int $sessionDayId, string $status, ?int $minutes, ?int $markedBy): void
    {
        $existing = $this->where('enrolment_id', $enrolmentId)->where('session_day_id', $sessionDayId)->first();

        $row = [
            'enrolment_id'   => $enrolmentId,
            'session_day_id' => $sessionDayId,
            'status'         => $status,
            'minutes'        => $minutes,
            'marked_by'      => $markedBy,
        ];

        $existing === null ? $this->insert($row) : $this->update((int) $existing['id'], $row);
    }

    /**
     * Whether a learner attended enough of a session to be certificated.
     *
     * A certificate is a statement that somebody was there. Issuing one to a
     * name that never appeared devalues every other certificate the school has
     * issued, so the rule is a number rather than a judgement call.
     */
    public function qualifies(int $enrolmentId, int $sessionId, int $thresholdPercent = 80): bool
    {
        $days = $this->db->table('session_days')->where('session_id', $sessionId)->countAllResults();
        if ($days === 0) {
            return true;   // self-paced: the quiz decides, not the register
        }

        $present = $this->whereIn('status', ['present', 'late'])
            ->where('enrolment_id', $enrolmentId)->countAllResults();

        return (int) round($present / $days * 100) >= $thresholdPercent;
    }
}
