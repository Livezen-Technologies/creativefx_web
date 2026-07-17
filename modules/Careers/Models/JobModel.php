<?php

namespace Modules\Careers\Models;

use CodeIgniter\Model;

class JobModel extends Model
{
    protected $table         = 'jobs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'title', 'description', 'department', 'country', 'location',
        'employment_type', 'experience', 'qualifications', 'skills', 'salary_range',
        'status', 'posted_at', 'closes_at',
    ];

    /** Open vacancies whose deadline (if any) has not passed, newest first. */
    public function openJobs(): array
    {
        return $this->where('status', 'open')
            ->groupStart()
                ->where('closes_at IS NULL')
                ->orWhere('closes_at >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy('posted_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();
    }

    public function findOpenBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->where('status', 'open')->first();
    }
}
