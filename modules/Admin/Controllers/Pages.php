<?php

namespace Modules\Admin\Controllers;

class Pages extends BaseCrudController
{
    protected string $modelClass = 'Modules\Cms\Models\PageModel';
    protected string $title      = 'Pages';
    protected string $singular   = 'Page';
    protected string $route      = 'pages';
    protected string $active     = 'pages';
    protected string $orderBy    = 'sort_order';

    protected array $extraActions = [
        ['label' => 'Content', 'path' => 'pages/{id}/content'],
    ];

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'template', 'label' => 'Template'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'URL segment, e.g. our-story'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'locale'],
        ['name' => 'template', 'label' => 'Template', 'help' => 'default or home'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
        ['name' => 'is_home', 'label' => 'Home page', 'type' => 'checkbox', 'help' => 'Render with the bespoke launch experience'],
    ];
}
