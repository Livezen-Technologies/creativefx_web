<?php

namespace Modules\Admin\Controllers;

/**
 * The course taxonomy.
 *
 * The slug is the URL — /courses/photoshop — so changing one breaks every link
 * anybody has ever shared to it. The field says so rather than leaving an
 * editor to find out from a support email.
 */
class CourseCategories extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\CourseCategoryModel';
    protected string $title      = 'Course categories';
    protected string $singular   = 'Category';
    protected string $route      = 'course-categories';
    protected string $active     = 'course-categories';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'pillar', 'label' => 'Pillar'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale', 'rules' => 'required|max_length[191]'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'This is the web address: /courses/{slug}. Changing it breaks every existing link to this category.'],
        ['name' => 'parent_id', 'label' => 'Parent category', 'type' => 'number', 'help' => 'The id of the branch this sits under. Leave blank for a top-level pillar.'],
        ['name' => 'pillar', 'label' => 'Pillar', 'type' => 'select', 'options' => ['adobe' => 'Adobe Creative Cloud', 'ai' => 'AI & Generative AI', 'design' => 'Design & Digital']],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale_richtext'],
        ['name' => 'icon', 'label' => 'Icon', 'help' => 'Optional icon key'],
        ['name' => 'hero_image', 'label' => 'Header image', 'type' => 'image'],
        ['name' => 'seo_title', 'label' => 'SEO title', 'type' => 'locale'],
        ['name' => 'seo_description', 'label' => 'SEO description', 'type' => 'locale_textarea'],
        ['name' => 'seo_keywords', 'label' => 'SEO keywords'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
