<?php

namespace Modules\Portfolio\Models;

use CodeIgniter\Model;

class PortfolioProjectModel extends Model
{
    protected $table         = 'portfolio_projects';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'category_id', 'slug', 'title', 'client', 'industry', 'service',
        'project_date', 'year', 'excerpt', 'description', 'challenge',
        'approach', 'results', 'cover_image', 'video_url', 'gallery',
        'featured', 'sort_order', 'status', 'meta_title', 'meta_description',
    ];

    /**
     * Scope: published work, newest shoot first. Projects without a date fall
     * to the bottom of their id order rather than jumping the queue.
     */
    public function published(?int $categoryId = null): self
    {
        $this->where('status', 'published')
            ->orderBy('project_date', 'DESC')
            ->orderBy('id', 'DESC');

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
     * Curated highlights for home/service pages: the featured flag first, in
     * the order an editor set with sort_order.
     *
     * @return list<array>
     */
    public function featured(int $limit = 6): array
    {
        return $this->where('status', 'published')
            ->where('featured', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('project_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    /**
     * Other published work from the same category (newest overall as filler, so
     * the strip at the foot of a detail page is never half empty).
     *
     * @return list<array>
     */
    public function related(array $project, int $limit = 3): array
    {
        $related = [];
        if (! empty($project['category_id'])) {
            $related = $this->published((int) $project['category_id'])
                ->where('id !=', (int) $project['id'])
                ->findAll($limit);
        }
        if (count($related) < $limit) {
            $exclude = array_merge([(int) $project['id']], array_column($related, 'id'));
            $filler  = $this->published()->whereNotIn('id', $exclude)->findAll($limit - count($related));
            $related = array_merge($related, $filler);
        }

        return $related;
    }
}
