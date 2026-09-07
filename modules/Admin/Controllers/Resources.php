<?php

namespace Modules\Admin\Controllers;

/**
 * Free downloads — the top of the funnel.
 *
 * `gated` decides whether the file is handed over straight away or in exchange
 * for an email. Both are legitimate; which one a given asset should be is a
 * marketing decision that changes, so it is a switch rather than two features.
 */
class Resources extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\ResourceModel';
    protected string $title      = 'Resources';
    protected string $singular   = 'Resource';
    protected string $route      = 'resources';
    protected string $active     = 'resources';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'type', 'label' => 'Type'],
        ['name' => 'gated', 'label' => 'Gated', 'type' => 'badge'],
        ['name' => 'download_count', 'label' => 'Downloads'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale', 'rules' => 'required|max_length[191]'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['cheatsheet' => 'Cheat sheet', 'guide' => 'Guide', 'checklist' => 'Checklist', 'template' => 'Template']],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea'],
        ['name' => 'body', 'label' => 'Page copy', 'type' => 'locale_richtext'],
        ['name' => 'file_path', 'label' => 'File', 'type' => 'file', 'help' => 'Leave blank while it is still being written — the page still publishes and still takes the email.'],
        ['name' => 'hero_image', 'label' => 'Image', 'type' => 'image'],
        ['name' => 'gated', 'label' => 'Ask for an email address first', 'type' => 'checkbox'],
        ['name' => 'seo_title', 'label' => 'SEO title', 'type' => 'locale'],
        ['name' => 'seo_description', 'label' => 'SEO description', 'type' => 'locale_textarea'],
        ['name' => 'seo_keywords', 'label' => 'SEO keywords'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
        ['name' => 'download_count', 'label' => 'Downloads', 'type' => 'static'],
    ];
}
