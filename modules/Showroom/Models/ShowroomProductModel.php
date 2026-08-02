<?php

namespace Modules\Showroom\Models;

use CodeIgniter\Model;

class ShowroomProductModel extends Model
{
    protected $table         = 'showroom_products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'showroom_category_id', 'slug', 'name', 'description', 'hotspot',
        'gallery', 'materials', 'fabric', 'moq', 'sizes', 'collection',
        'image', 'images', 'model_path', 'brochure_path', 'sort_order', 'status',
    ];
}
