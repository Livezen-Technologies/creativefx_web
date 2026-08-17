<?php

namespace Modules\Gear\Models;

use CodeIgniter\Model;

/**
 * The rental catalogue. Everything that lists kit for hire — the gear_grid CMS
 * block today, a gear index page later — reads through this model, so the
 * order and the availability rules stay identical wherever kit appears.
 */
class GearItemModel extends Model
{
    protected $table         = 'gear_items';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'category_id', 'slug', 'name', 'summary', 'specs', 'image',
        'rate_daily', 'rate_weekly', 'currency', 'availability',
        'featured', 'sort_order', 'status',
    ];

    /**
     * Scope: published kit in shelf order — the flagship items an editor
     * flagged first, then their own sort_order, with id breaking ties so the
     * order is stable when two rows share a sort_order.
     *
     * Kit that is out or in the workshop stays in the list: a booked camera is
     * still worth a quote request for another date, and the card says so.
     */
    public function published(?int $categoryId = null): self
    {
        $this->where('status', 'published')
            ->orderBy('featured', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC');

        if ($categoryId !== null) {
            $this->where('category_id', $categoryId);
        }

        return $this;
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->published()->where('slug', $slug)->first();
    }

    /**
     * Curated highlights for a home or service page: the featured flag only,
     * in the order an editor set with sort_order.
     *
     * @return list<array>
     */
    public function featured(int $limit = 6): array
    {
        return $this->where('status', 'published')
            ->where('featured', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll($limit);
    }

    /**
     * Kit that can go out today, for a "what's in right now" listing.
     *
     * @return list<array>
     */
    public function availableNow(?int $categoryId = null, int $limit = 0): array
    {
        return $this->published($categoryId)
            ->where('availability', 'available')
            ->findAll($limit);
    }
}
