<?php

namespace Modules\Admin\Controllers;

/**
 * Gear rental categories: the kit families an item is filed under. A gear_grid
 * block filters on the slug ({"category": "cameras"}), so the slug is the
 * stable handle — rename the name, never the slug.
 */
class GearCategories extends BaseCrudController
{
    protected string $modelClass = 'Modules\Gear\Models\GearCategoryModel';
    protected string $title      = 'Gear Categories';
    protected string $singular   = 'Gear Category';
    protected string $route      = 'gear-categories';
    protected string $active     = 'gear-categories';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'The handle a gear_grid block filters on, e.g. cameras, lighting, audio'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale', 'help' => 'Heading shown above the group of cards, e.g. Cameras'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|is_natural', 'help' => 'Low numbers first'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft'], 'help' => 'A draft category keeps its items; they are simply unfiled on the site'],
    ];

    protected function collect(): array
    {
        $data = parent::collect();
        // An empty number input posts '', which a strict-mode INT column rejects.
        $data['sort_order'] = (int) $data['sort_order'];

        return $data;
    }
}
