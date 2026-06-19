<?php

namespace Modules\Cms\Models;

use CodeIgniter\Model;

class PageSectionModel extends Model
{
    protected $table         = 'page_sections';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['page_id', 'key', 'type', 'settings', 'sort_order', 'status'];
}
