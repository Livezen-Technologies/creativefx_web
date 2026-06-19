<?php

namespace Modules\Cms\Models;

use CodeIgniter\Model;

class PageBlockModel extends Model
{
    protected $table         = 'page_blocks';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['section_id', 'type', 'content', 'sort_order', 'status'];
}
