<?php

namespace Modules\Admin\Controllers;

class Categories extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\ProductCategoryModel';
    protected string $title      = 'Product Categories';
    protected string $singular   = 'Category';
    protected string $route      = 'categories';
    protected string $active     = 'categories';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale'],
        ['name' => 'image_path', 'label' => 'Image path'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
    ];
}
