<?php

namespace Modules\Admin\Controllers;

class NewsCategories extends BaseCrudController
{
    protected string $modelClass = 'Modules\News\Models\NewsCategoryModel';
    protected string $title      = 'News Categories';
    protected string $singular   = 'News Category';
    protected string $route      = 'news-categories';
    protected string $active     = 'news-categories';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'URL segment, e.g. press-releases'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
