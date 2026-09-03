<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class StatisticModel extends Model
{
    protected $table         = 'statistics_datasets';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'title', 'description', 'unit', 'chart', 'columns', 'rows',
        'source', 'period', 'sort_order', 'status',
    ];

    /** @return list<array> */
    public function live(): array
    {
        return $this->where('status', 'published')->orderBy('sort_order', 'ASC')->findAll();
    }

    public function findLive(string $slug): ?array
    {
        return $this->where('status', 'published')->where('slug', $slug)->first();
    }

    /**
     * Decode a dataset's columns and rows once, here, so no view has to know
     * they are stored as JSON — and so a malformed dataset renders as an empty
     * table rather than a PHP warning on a public page.
     */
    public static function decode(array $dataset): array
    {
        $columns = json_decode((string) ($dataset['columns'] ?? '[]'), true);
        $rows    = json_decode((string) ($dataset['rows'] ?? '[]'), true);

        return [
            is_array($columns) ? $columns : [],
            is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [],
        ];
    }
}
