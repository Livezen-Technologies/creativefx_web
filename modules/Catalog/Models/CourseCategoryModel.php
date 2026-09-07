<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * The course taxonomy: Adobe Creative Cloud → Photoshop → the courses.
 *
 * `course_categories`, not `categories` — the News module already owns that
 * name for article categories, and one word covering two different trees is a
 * bug waiting for whoever writes the next join.
 *
 * Two levels are used in practice (pillar branch, then application), but
 * parent_id imposes no limit; the URL builder walks whatever depth exists.
 */
class CourseCategoryModel extends Model
{
    protected $table         = 'course_categories';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'parent_id', 'slug', 'name', 'summary', 'description', 'icon', 'pillar',
        'hero_image', 'seo_title', 'seo_description', 'seo_keywords',
        'sort_order', 'status',
    ];

    public function live(): self
    {
        $this->where('status', 'published')->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');

        return $this;
    }

    public function findLive(string $slug): ?array
    {
        return $this->live()->where('slug', $slug)->first();
    }

    /**
     * The whole tree in one query, nested.
     *
     * One query rather than one per branch: the footer prints the entire
     * category link farm on every page of the site — it is the internal linking
     * the SEO plan rests on — so this runs everywhere and must be cheap.
     *
     * @return list<array> each row gains a 'children' key
     */
    public function tree(?string $pillar = null): array
    {
        $rows = $this->live()
            ->when($pillar !== null, static fn ($q) => $q->where('pillar', $pillar))
            ->findAll();

        $byParent = [];
        foreach ($rows as $row) {
            $byParent[(int) ($row['parent_id'] ?? 0)][] = $row;
        }

        $attach = static function (array $nodes) use (&$attach, $byParent): array {
            foreach ($nodes as &$node) {
                $node['children'] = $attach($byParent[(int) $node['id']] ?? []);
            }

            return $nodes;
        };

        return $attach($byParent[0] ?? []);
    }

    /**
     * A category and every category beneath it, as ids.
     *
     * What "show me everything under Adobe Creative Cloud" needs: a course sits
     * in a leaf, so filtering the catalogue by a branch means collecting the
     * whole subtree first.
     *
     * @return list<int>
     */
    public function descendantIds(int $categoryId): array
    {
        $all = $this->live()->findAll();

        $ids  = [$categoryId];
        $more = true;
        while ($more) {
            $more = false;
            foreach ($all as $row) {
                if (in_array((int) $row['parent_id'], $ids, true) && ! in_array((int) $row['id'], $ids, true)) {
                    $ids[] = (int) $row['id'];
                    $more  = true;
                }
            }
        }

        return $ids;
    }

    /**
     * The breadcrumb trail to a category, root first.
     *
     * @return list<array>
     */
    public function ancestors(array $category): array
    {
        $trail = [$category];
        $seen  = [(int) $category['id']];

        while (! empty($trail[0]['parent_id'])) {
            $parent = $this->find((int) $trail[0]['parent_id']);
            // A cycle in the tree is somebody's data-entry accident, not an
            // impossibility. Without this guard it is an infinite loop that
            // takes the page down rather than a wrong breadcrumb.
            if ($parent === null || in_array((int) $parent['id'], $seen, true)) {
                break;
            }
            array_unshift($trail, $parent);
            $seen[] = (int) $parent['id'];
        }

        return $trail;
    }
}
