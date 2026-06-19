<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table         = 'products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['category_id', 'slug', 'name', 'description', 'attributes', 'hero_image', 'sort_order', 'status'];
}
