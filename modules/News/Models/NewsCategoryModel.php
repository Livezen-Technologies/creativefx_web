<?php

namespace Modules\News\Models;

use CodeIgniter\Model;

class NewsCategoryModel extends Model
{
    protected $table         = 'news_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['slug', 'name', 'description', 'sort_order', 'status'];

    /** @return list<array> */
    public function published(): array
    {
        return $this->where('status', 'published')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
