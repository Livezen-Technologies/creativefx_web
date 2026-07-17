<?php

namespace Modules\Cms\Models;

use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table          = 'pages';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'slug', 'title', 'meta_title', 'meta_description', 'og_image',
        'template', 'is_home', 'parent_id', 'sort_order', 'status', 'publish_at',
    ];

    public function findPublishedBySlug(string $slug): ?array
    {
        // Scheduled publishing: a published page stays hidden until publish_at.
        return $this->where('slug', $slug)
            ->where('status', 'published')
            ->groupStart()
                ->where('publish_at IS NULL')
                ->orWhere('publish_at <=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->first();
    }

    public function findHome(): ?array
    {
        return $this->where('is_home', 1)->where('status', 'published')->first();
    }

    /**
     * Attach the ordered sections (each with ordered blocks) to a page array.
     */
    public function withStructure(array $page): array
    {
        $sections = (new PageSectionModel())
            ->where('page_id', $page['id'])
            ->where('status', 'published')
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $blockModel = new PageBlockModel();
        foreach ($sections as &$section) {
            $section['blocks'] = $blockModel
                ->where('section_id', $section['id'])
                ->where('status', 'published')
                ->orderBy('sort_order', 'ASC')
                ->findAll();
        }
        unset($section);

        $page['sections'] = $sections;

        return $page;
    }
}
