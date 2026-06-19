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
        'employment_type', 'status', 'posted_at', 'closes_at',
    ];
}
