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
        'slug', 'title', 'meta_title', 'meta_description',
        'template', 'is_home', 'parent_id', 'sort_order', 'status',
    ];

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->where('slug', $slug)->where('status', 'published')->first();
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
