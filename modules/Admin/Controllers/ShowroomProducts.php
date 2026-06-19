<?php

namespace Modules\Admin\Controllers;

class ShowroomProducts extends BaseCrudController
{
    protected string $modelClass = 'Modules\Showroom\Models\ShowroomProductModel';
    protected string $title      = 'Showroom Products';
    protected string $singular   = 'Showroom Product';
    protected string $route      = 'showroom-products';
    protected string $active     = 'showroom-products';
    protected string $orderBy    = 'id';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'showroom_category_id', 'label' => 'Category ID', 'type' => 'number', 'rules' => 'required|is_natural_no_zero', 'help' => 'Showroom category id'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale'],
        ['name' => 'hotspot', 'label' => 'Hotspot', 'type' => 'textarea', 'help' => 'JSON {"x":0,"y":1.2,"z":0}'],
        ['name' => 'gallery', 'label' => 'Gallery', 'type' => 'textarea', 'help' => 'JSON array of hex colours / image URLs'],
        ['name' => 'materials', 'label' => 'Materials', 'type' => 'textarea', 'help' => 'JSON array of materials'],
        ['name' => 'brochure_path', 'label' => 'Brochure path'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
    ];
}
