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
        ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'locale', 'help' => 'Shown under the title in search results — aim for 150–160 characters'],
        ['name' => 'meta_keywords', 'label' => 'Meta keywords', 'type' => 'locale', 'help' => 'Comma-separated. Google has not used these for many years; harmless, and other engines still read them'],
        ['name' => 'canonical_url', 'label' => 'Canonical URL', 'help' => 'Leave blank unless two addresses serve this same page — blank means the page is its own canonical'],
        ['name' => 'og_title', 'label' => 'Share title', 'type' => 'locale', 'help' => 'Used on social cards. Blank falls back to the SEO title, then the page title'],
        ['name' => 'og_description', 'label' => 'Share description', 'type' => 'locale', 'help' => 'Blank falls back to the meta description'],
        ['name' => 'og_image', 'label' => 'Share image', 'help' => 'Path to a share image, e.g. /media/uploads/… (upload via Media). 1200×630 reads best'],
        ['name' => 'template', 'label' => 'Template', 'help' => 'default or home'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
        ['name' => 'publish_at', 'label' => 'Publish schedule', 'help' => 'Optional, YYYY-MM-DD HH:MM:SS — page goes live at this time'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'is_home', 'label' => 'Home page', 'type' => 'checkbox', 'help' => 'Render with the bespoke launch experience'],
        // A real control, not a readonly display: the page builder ticks it on
        // the first edit, and unticking it here is the deliberate way to hand a
        // page back so future releases can update its copy again.
        ['name' => 'is_custom', 'label' => 'This page\'s content is managed here', 'type' => 'checkbox', 'help' => 'Ticked automatically the first time the page builder is used on this page. While ticked, a site release will not touch its sections or blocks. Untick to let releases update this page again — the next one will replace its content'],
    ];
}
