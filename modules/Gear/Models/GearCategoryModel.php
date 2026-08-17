<?php

namespace Modules\Gear\Models;

use CodeIgniter\Model;

class GearCategoryModel extends Model
{
    protected $table         = 'gear_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['slug', 'name', 'sort_order', 'status'];

    /** @return list<array> */
    public function published(): array
    {
        return $this->where('status', 'published')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->where('status', 'published')
            ->where('slug', $slug)
            ->first();
    }
}
