<?php

namespace Modules\Esg\Models;

use CodeIgniter\Model;

class EsgReportModel extends Model
{
    protected $table         = 'esg_reports';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['title', 'summary', 'year', 'file_path', 'status', 'published_at'];
}
