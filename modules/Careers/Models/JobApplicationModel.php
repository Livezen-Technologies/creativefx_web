<?php

namespace Modules\Careers\Models;

use CodeIgniter\Model;

class JobApplicationModel extends Model
{
    /** HR pipeline stages, in order (blueprint §14). */
    public const STATUSES = ['new', 'reviewing', 'shortlisted', 'interview', 'selected', 'rejected', 'hired'];

    protected $table         = 'job_applications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'job_id', 'name', 'email', 'phone', 'country', 'address', 'education',
        'experience', 'skills', 'linkedin', 'resume_path', 'portfolio_path',
        'cover_letter', 'status', 'notes', 'rating',
    ];

    /** Application counts per pipeline status (missing stages => 0). */
    public function countsByStatus(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($this->select('status, COUNT(*) AS n')->groupBy('status')->findAll() as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        return $counts;
    }
}
