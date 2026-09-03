<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class OfficeModel extends Model
{
    protected $table         = 'offices';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'name', 'kind', 'district', 'province', 'address', 'phone',
        'fax', 'email', 'latitude', 'longitude', 'sort_order', 'status',
    ];

    /** @return list<array> */
    public function live(?string $kind = null): array
    {
        $this->where('status', 'published')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');
        if ($kind !== null) {
            $this->where('kind', $kind);
        }

        return $this->findAll();
    }

    public function head(): ?array
    {
        return $this->where('status', 'published')->where('kind', 'head')->first();
    }

    /** Offices that can be put on a map — the rest would land at 0,0. */
    public function mappable(): array
    {
        return $this->where('status', 'published')
            ->where('latitude IS NOT NULL')
            ->where('longitude IS NOT NULL')
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }
}
