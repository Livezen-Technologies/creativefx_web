<?php

namespace Modules\Admin\Controllers;

/**
 * Classrooms, and the virtual room a live online class runs in.
 *
 * Both are venues, because a session needs a timezone whether or not anybody
 * travels to it — and the timezone is what makes a schedule readable to
 * somebody three hours away.
 *
 * The body copy is a local SEO page in its own right. Templated city pages —
 * the same paragraph with the city name swapped — are exactly what a search
 * engine is looking for when it decides a site is thin, so each one is written
 * separately or not published.
 */
class Venues extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\VenueModel';
    protected string $title      = 'Venues';
    protected string $singular   = 'Venue';
    protected string $route      = 'venues';
    protected string $active     = 'venues';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Name'],
        ['name' => 'city', 'label' => 'City'],
        ['name' => 'type', 'label' => 'Type'],
        ['name' => 'capacity', 'label' => 'Seats'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Name', 'rules' => 'required|max_length[128]'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['classroom' => 'Classroom', 'virtual' => 'Virtual']],
        ['name' => 'city', 'label' => 'City'],
        ['name' => 'country', 'label' => 'Country code', 'help' => 'Two letters, e.g. LK'],
        ['name' => 'timezone', 'label' => 'Timezone', 'help' => 'e.g. Asia/Colombo. Every session here is displayed in this zone alongside the reader’s own.'],
        ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number'],
        ['name' => 'address', 'label' => 'Address', 'type' => 'locale_textarea', 'help' => 'Leave blank rather than approximate. A wrong address sends somebody to the wrong building.'],
        ['name' => 'map_url', 'label' => 'Map link'],
        ['name' => 'directions', 'label' => 'How to find it', 'type' => 'locale_richtext'],
        ['name' => 'heading', 'label' => 'Page heading', 'type' => 'locale'],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea'],
        ['name' => 'body', 'label' => 'Page copy', 'type' => 'locale_richtext', 'help' => 'Genuinely about this city. Not the same paragraph with the name changed.'],
        ['name' => 'seo_title', 'label' => 'SEO title', 'type' => 'locale'],
        ['name' => 'seo_description', 'label' => 'SEO description', 'type' => 'locale_textarea'],
        ['name' => 'seo_keywords', 'label' => 'SEO keywords'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
