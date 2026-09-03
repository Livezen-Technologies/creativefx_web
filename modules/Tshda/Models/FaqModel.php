<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class FaqModel extends Model
{
    protected $table         = 'faqs';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['category', 'question', 'answer', 'sort_order', 'status'];

    /**
     * Every published question, grouped by category and in display order.
     *
     * @return array<string, list<array>>
     */
    public function grouped(): array
    {
        $out = [];
        foreach ($this->where('status', 'published')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll() as $row) {
            $out[$row['category'] ?: 'general'][] = $row;
        }

        return $out;
    }
}
