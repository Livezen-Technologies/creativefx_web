<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table         = 'products';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'category_id', 'slug', 'sku', 'code', 'collection', 'name', 'short_description',
        'description', 'attributes', 'features', 'applications', 'specs', 'hero_image',
        'gallery', 'model_path', 'brochure_path', 'certifications', 'label', 'is_featured',
        'meta_title', 'meta_description', 'sort_order', 'status',
    ];

    /** Merchandising labels (blueprint §9). */
    public const LABELS = ['new', 'bestseller', 'popular'];

    /** Scope: published products, featured first then curated order. */
    public function published(?int $categoryId = null, ?string $label = null): self
    {
        $this->where('status', 'published')
            ->orderBy('is_featured', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC');

        if ($categoryId !== null) {
            $this->where('category_id', $categoryId);
        }
        if ($label !== null && $label !== '') {
            $label === 'featured' ? $this->where('is_featured', 1) : $this->where('label', $label);
        }

        return $this;
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->where('status', 'published')->where('slug', $slug)->first();
    }

    /** @return list<array> */
    public function featured(int $limit = 6): array
    {
        return $this->published(null, 'featured')->findAll($limit);
    }

    /** Other published products from the same category (newest fill). */
    public function related(array $product, int $limit = 3): array
    {
        $related = [];
        if (! empty($product['category_id'])) {
            $related = $this->published((int) $product['category_id'])
                ->where('id !=', (int) $product['id'])
                ->findAll($limit);
        }
        if (count($related) < $limit) {
            $exclude = array_merge([(int) $product['id']], array_column($related, 'id'));
            $filler  = $this->published()->whereNotIn('id', $exclude)->findAll($limit - count($related));
            $related = array_merge($related, $filler);
        }
        return $related;
    }
}
