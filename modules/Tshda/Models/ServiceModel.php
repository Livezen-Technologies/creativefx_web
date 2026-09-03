<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class ServiceModel extends Model
{
    protected $table         = 'services';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'area', 'audience', 'title', 'summary', 'eligibility', 'process',
        'documents', 'fee', 'duration', 'division', 'contact_point', 'form_url',
        'window_open', 'icon', 'sort_order', 'status',
    ];

    public function live(): self
    {
        $this->where('status', 'published')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');

        return $this;
    }

    /** @return list<array> */
    public function byArea(string $area): array
    {
        return $this->live()->where('area', $area)->findAll();
    }

    /** @return list<array> */
    public function byAudience(string $audience, int $limit = 0): array
    {
        return $this->live()->where('audience', $audience)->findAll($limit ?: null);
    }

    public function findLive(string $slug): ?array
    {
        return $this->live()->where('slug', $slug)->first();
    }

    /**
     * Services grouped by the stakeholder they are for — the shape the home
     * page's service clusters need (Clause 3.9 B.I). Returned in the order the
     * clusters are shown rather than the order the rows come back in, so an
     * empty cluster is a missing key and not a hole in the middle of the grid.
     *
     * @return array<string, list<array>>
     */
    public function clusters(): array
    {
        $out = [];
        foreach ($this->live()->findAll() as $row) {
            $out[$row['audience'] ?: 'smallholder'][] = $row;
        }

        return $out;
    }
}
