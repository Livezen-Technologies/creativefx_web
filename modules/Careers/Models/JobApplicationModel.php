<?php

namespace Modules\Careers\Models;

use CodeIgniter\Model;

class JobApplicationModel extends Model
{
    protected $table         = 'job_applications';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'job_id', 'name', 'email', 'phone', 'resume_path', 'portfolio_path', 'cover_letter', 'status',
    ];
}
