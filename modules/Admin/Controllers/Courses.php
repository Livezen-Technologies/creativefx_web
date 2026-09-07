<?php

namespace Modules\Admin\Controllers;

/**
 * Courses — the evergreen half of the catalogue.
 *
 * This screen edits what a course *is*. What it costs and when it runs are
 * sessions, and they have their own screen: a course has no price and no date,
 * which is the split the whole schema turns on.
 *
 * `is_custom` is the flag that stops a release overwriting editorial work. It
 * is set automatically on any save here, and is shown read-only rather than as
 * a checkbox — a checkbox would let somebody clear it without realising that
 * the next deploy would then rewrite everything they had just written.
 */
class Courses extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\CourseModel';
    protected string $title      = 'Courses';
    protected string $singular   = 'Course';
    protected string $route      = 'courses';
    protected string $active     = 'courses';
    protected string $orderBy    = 'pillar';

    protected array $extraActions = [
        ['label' => 'Dates', 'path' => 'course-sessions?course={id}'],
    ];

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Course', 'type' => 'locale'],
        ['name' => 'pillar', 'label' => 'Pillar'],
        ['name' => 'level', 'label' => 'Level'],
        ['name' => 'price_band', 'label' => 'Band'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale', 'rules' => 'required|max_length[191]'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'This is the web address: /course/{slug}. Changing it breaks every existing link, every advert and every search result.'],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'locale'],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea', 'help' => 'Up to 160 characters. Used as the meta description and on every card.'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale_richtext'],
        ['name' => 'category_id', 'label' => 'Category', 'type' => 'number', 'help' => 'The id from Course categories'],
        ['name' => 'pillar', 'label' => 'Pillar', 'type' => 'select', 'options' => ['adobe' => 'Adobe Creative Cloud', 'ai' => 'AI & Generative AI', 'design' => 'Design & Digital']],
        ['name' => 'level', 'label' => 'Level', 'type' => 'select', 'options' => [1 => 'Beginner', 2 => 'Intermediate', 3 => 'Advanced']],
        ['name' => 'duration_days', 'label' => 'Days', 'type' => 'number'],
        ['name' => 'duration_hours', 'label' => 'Taught hours', 'type' => 'number'],
        ['name' => 'software_version', 'label' => 'Software version', 'help' => 'e.g. "Creative Cloud 2026". Answers the "is this current?" objection.'],
        ['name' => 'certification_alignment', 'label' => 'Certification alignment', 'type' => 'locale_textarea', 'help' => 'Which Adobe Certified Professional exam this prepares for. Say "prepares for" — the school does not award it.'],
        ['name' => 'price_band', 'label' => 'Price band', 'type' => 'select', 'options' => ['1day' => 'One day', '2day' => 'Two days', 'bootcamp' => 'Bootcamp', 'selfpaced' => 'Self-paced']],
        ['name' => 'default_mode', 'label' => 'Default mode', 'type' => 'select', 'options' => ['LIVE_ONLINE' => 'Live online', 'CLASSROOM' => 'In person', 'SELF_PACED' => 'Self-paced', 'PRIVATE' => 'Private']],
        ['name' => 'hero_image', 'label' => 'Header image', 'type' => 'image'],
        ['name' => 'is_featured', 'label' => 'Feature on the home page', 'type' => 'checkbox'],
        ['name' => 'seo_title', 'label' => 'SEO title', 'type' => 'locale'],
        ['name' => 'seo_description', 'label' => 'SEO description', 'type' => 'locale_textarea'],
        ['name' => 'seo_keywords', 'label' => 'SEO keywords'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
        ['name' => 'rating_avg', 'label' => 'Average rating', 'type' => 'static', 'help' => 'Calculated from approved reviews. Never entered by hand.'],
        ['name' => 'rating_count', 'label' => 'Reviews', 'type' => 'static'],
    ];

    /**
     * Claim the row on save, so the catalogue seeder leaves it alone from now on.
     *
     * Done here rather than as a field, because it is a consequence of editing
     * rather than a decision an editor makes — and because a checkbox somebody
     * can clear is a checkbox that will be cleared, after which the next deploy
     * quietly overwrites their work.
     */
    protected function collect(): array
    {
        return parent::collect() + ['is_custom' => 1];
    }
}
