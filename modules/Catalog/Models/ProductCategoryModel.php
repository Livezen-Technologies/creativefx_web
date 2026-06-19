<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

class ProductCategoryModel extends Model
{
    protected $table         = 'product_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['slug', 'name', 'description', 'image_path', 'parent_id', 'sort_order', 'status'];

    public function published(): array
    {
        return $this->where('status', 'published')->orderBy('sort_order', 'ASC')->findAll();
    }
}
