<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class DocumentCategoryModel extends Model
{
    protected $table         = 'document_categories';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['slug', 'name', 'sort_order', 'status'];

    /** @return list<array> */
    public function live(): array
    {
        return $this->where('status', 'published')->orderBy('sort_order', 'ASC')->findAll();
    }
}
