<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class SocietyModel extends Model
{
    protected $table         = 'societies';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'registration', 'name', 'kind', 'district', 'division', 'office_id',
        'members', 'secretary', 'phone', 'registered_on', 'status',
    ];

    /** @return list<array> */
    public function search(array $filters = [], int $limit = 100): array
    {
        $this->where('status', 'published');

        if (! empty($filters['district'])) {
            $this->where('district', $filters['district']);
        }
        if (! empty($filters['kind'])) {
            $this->where('kind', $filters['kind']);
        }
        if (! empty($filters['q'])) {
            $this->groupStart()
                ->like('name', $filters['q'])
                ->orLike('registration', $filters['q'])
                ->orLike('division', $filters['q'])
              ->groupEnd();
        }

        return $this->orderBy('district', 'ASC')->orderBy('name', 'ASC')->findAll($limit);
    }

    /** @return list<string> */
    public function districts(): array
    {
        $rows = $this->select('district')->distinct()
            ->where('status', 'published')->where('district IS NOT NULL')
            ->orderBy('district', 'ASC')->findAll();

        return array_values(array_filter(array_column($rows, 'district')));
    }
}
