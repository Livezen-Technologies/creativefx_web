<?php

namespace Modules\Services\Models;

use CodeIgniter\Model;

/**
 * The services registry. Everything that lists what CreativeFX sells — the
 * header dropdown, the footer, the Services overview grid, related-service
 * links on a service page — reads through this model, so the order and the
 * wording stay identical wherever they appear.
 */
class ServiceModel extends Model
{
    protected $table         = 'services';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'name', 'tagline', 'summary', 'icon',
        'card_image', 'hero_image', 'sort_order', 'status',
    ];

    /**
     * Every published service in editor-defined order (id breaks ties so the
     * order is stable when two rows share a sort_order).
     *
     * @return list<array>
     */
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

    /**
     * The other published services, for the "explore more" strip at the foot
     * of a service page. Wraps around the sort order rather than stopping at
     * the end, so the last service still gets a full set of siblings.
     *
     * @return list<array>
     */
    public function related(string $slug, int $limit = 3): array
    {
        $all    = $this->published();
        $others = [];
        $start  = 0;

        foreach ($all as $i => $service) {
            if ($service['slug'] === $slug) {
                $start = $i;
                continue;
            }
            $others[] = $service;
        }

        if ($others === []) {
            return [];
        }

        $start %= count($others);

        return array_slice(array_merge(array_slice($others, $start), array_slice($others, 0, $start)), 0, $limit);
    }
}
