<?php

namespace Modules\Portfolio\Models;

use CodeIgniter\Model;

class PortfolioCategoryModel extends Model
{
    protected $table         = 'portfolio_categories';
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
}
