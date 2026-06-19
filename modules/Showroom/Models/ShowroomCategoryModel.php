<?php

namespace Modules\Showroom\Models;

use CodeIgniter\Model;

class ShowroomCategoryModel extends Model
{
    protected $table         = 'showroom_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['slug', 'name', 'theme', 'background', 'sort_order', 'status'];
}
