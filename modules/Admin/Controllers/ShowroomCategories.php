<?php

namespace Modules\Admin\Controllers;

class ShowroomCategories extends BaseCrudController
{
    protected string $modelClass = 'Modules\Showroom\Models\ShowroomCategoryModel';
    protected string $title      = 'Showroom Categories';
    protected string $singular   = 'Showroom Category';
    protected string $route      = 'showroom-categories';
    protected string $active     = 'showroom-cats';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'theme', 'label' => 'Theme'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'theme', 'label' => 'Theme', 'help' => 'e.g. clouds, night, arena — drives the 3D accent'],
        ['name' => 'background', 'label' => 'Accent colour', 'help' => 'Hex, e.g. #FFC107'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
    ];
}
