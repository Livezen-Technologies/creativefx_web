<?php

namespace Modules\Cms\Models;

use CodeIgniter\Model;

class MenuItemModel extends Model
{
    protected $table          = 'menu_items';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'location', 'parent_id', 'label', 'url', 'target', 'sort_order', 'status',
    ];

    /**
     * One menu, ordered, with children nested under their parents.
     *
     * Drafts are excluded here rather than by the caller: this is what the
     * public site renders, and a hidden item that still appears because one
     * template forgot to filter is the whole point of hiding it defeated.
     */
    public function tree(string $location): array
    {
        $rows = $this->where('location', $location)
            ->where('status', 'published')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $byParent = [];
        foreach ($rows as $row) {
            $byParent[(int) ($row['parent_id'] ?? 0)][] = $row;
        }

        $tree = [];
        foreach ($byParent[0] ?? [] as $row) {
            // A child of a draft parent is not promoted to the top level: it was
            // put inside that parent, and hiding the parent hides the group.
            $row['children'] = $byParent[(int) $row['id']] ?? [];
            $tree[]          = $row;
        }

        return $tree;
    }
}
