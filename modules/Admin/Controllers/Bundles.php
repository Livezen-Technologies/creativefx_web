<?php

namespace Modules\Admin\Controllers;

/**
 * Bootcamps and certificate programmes.
 *
 * The courses inside a bundle and its per-currency price are separate tables,
 * so they are edited on their own screens; this is the marketing record. A
 * bundle with no items publishes as a programme that contains nothing, which is
 * why the list column counts them.
 */
class Bundles extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\BundleModel';
    protected string $title      = 'Programmes';
    protected string $singular   = 'Programme';
    protected string $route      = 'bundles';
    protected string $active     = 'bundles';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Programme', 'type' => 'locale'],
        ['name' => 'type', 'label' => 'Type'],
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale', 'rules' => 'required|max_length[191]'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['certificate' => 'Certificate programme', 'bootcamp' => 'Bootcamp']],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'locale'],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale_richtext'],
        ['name' => 'hero_image', 'label' => 'Header image', 'type' => 'image'],
        ['name' => 'seo_title', 'label' => 'SEO title', 'type' => 'locale'],
        ['name' => 'seo_description', 'label' => 'SEO description', 'type' => 'locale_textarea'],
        ['name' => 'seo_keywords', 'label' => 'SEO keywords'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];

    protected function collect(): array
    {
        return parent::collect() + ['is_custom' => 1];
    }
}
