<?php

namespace Modules\Esg\Models;

use CodeIgniter\Model;

class EsgMetricModel extends Model
{
    protected $table         = 'esg_metrics';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['key', 'label', 'value', 'unit', 'pillar', 'year', 'sort_order'];
}
