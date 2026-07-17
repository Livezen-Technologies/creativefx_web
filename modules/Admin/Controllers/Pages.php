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
        ['name' => 'meta_title', 'label' => 'SEO meta title', 'type' => 'locale', 'help' => 'Overrides the browser-tab / search-result title'],
        ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'locale'],
        ['name' => 'og_image', 'label' => 'Open Graph image', 'help' => 'Path to a share image, e.g. /media/uploads/… (upload via Media)'],
        ['name' => 'template', 'label' => 'Template', 'help' => 'default or home'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
        ['name' => 'publish_at', 'label' => 'Publish schedule', 'help' => 'Optional, YYYY-MM-DD HH:MM:SS — page goes live at this time'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'is_home', 'label' => 'Home page', 'type' => 'checkbox', 'help' => 'Render with the bespoke launch experience'],
    ];
}
