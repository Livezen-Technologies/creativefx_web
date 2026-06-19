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

    public function published(): array
    {
        return $this->where('status', 'published')->orderBy('sort_order', 'ASC')->findAll();
    }

    /** A published category with its published products attached. */
    public function findWithProducts(string $slug): ?array
    {
        $category = $this->where('slug', $slug)->where('status', 'published')->first();
        if ($category === null) {
            return null;
        }

        $category['products'] = (new ShowroomProductModel())
            ->where('showroom_category_id', $category['id'])
            ->where('status', 'published')
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        return $category;
    }
}

