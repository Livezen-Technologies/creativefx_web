<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class OfficerSubmissionModel extends Model
{
    protected $table         = 'officer_submissions';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'reference', 'user_id', 'office_id', 'kind', 'subject', 'body',
        'payload', 'attachment', 'attachment_name', 'status', 'reviewed_by',
        'reviewed_at', 'officer_note',
    ];

    /** The submissions one officer may see — their own, newest first. */
    public function forUser(int $userId, int $limit = 50): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')->findAll($limit);
    }

    /** @return list<array> */
    public function queue(?string $status = null): array
    {
        $this->select('officer_submissions.*, users.first_name AS officer_name, offices.name AS office_name')
            ->join('users', 'users.id = officer_submissions.user_id', 'left')
            ->join('offices', 'offices.id = officer_submissions.office_id', 'left')
            ->orderBy('officer_submissions.created_at', 'DESC');

        if ($status !== null && $status !== '') {
            $this->where('officer_submissions.status', $status);
        }

        return $this->findAll();
    }
}
