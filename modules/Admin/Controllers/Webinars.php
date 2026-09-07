<?php

namespace Modules\Admin\Controllers;

/**
 * Free live sessions.
 *
 * Deliberately not course sessions: a webinar is free, has no seat inventory
 * worth enforcing and is usually hosted somewhere else, so putting it through
 * the commerce pipeline would mean a zero-priced order for every registration.
 *
 * Fill in the recording afterwards. A past webinar with one keeps earning; one
 * without is an old date, and the listing treats them differently.
 */
class Webinars extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\WebinarModel';
    protected string $title      = 'Webinars';
    protected string $singular   = 'Webinar';
    protected string $route      = 'webinars';
    protected string $active     = 'webinars';
    protected string $orderBy    = 'starts_at';
    protected string $orderDir   = 'DESC';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'starts_at', 'label' => 'Starts'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale', 'rules' => 'required|max_length[191]'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea'],
        ['name' => 'body', 'label' => 'Description', 'type' => 'locale_richtext'],
        ['name' => 'starts_at', 'label' => 'Starts at', 'help' => 'YYYY-MM-DD HH:MM:SS, in the timezone below'],
        ['name' => 'duration_min', 'label' => 'Length in minutes', 'type' => 'number'],
        ['name' => 'timezone', 'label' => 'Timezone', 'help' => 'e.g. Asia/Colombo'],
        ['name' => 'register_url', 'label' => 'Registration link'],
        ['name' => 'recording_url', 'label' => 'Recording link', 'help' => 'Added after the event. Without one, a past webinar is not listed.'],
        ['name' => 'course_id', 'label' => 'Related course id', 'type' => 'number'],
        ['name' => 'hero_image', 'label' => 'Image', 'type' => 'image'],
        ['name' => 'seo_title', 'label' => 'SEO title', 'type' => 'locale'],
        ['name' => 'seo_description', 'label' => 'SEO description', 'type' => 'locale_textarea'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
