<?php

namespace Modules\Admin\Controllers;

use Modules\Cms\Models\LocationModel;

/**
 * Tourist locations — the places worth leaving the hotel for.
 *
 * Six of these were items inside one feature-cards block on the Kalawana page.
 * As records they can be added, reordered and hidden without touching a page,
 * and the same place can be shown on more than one page without its copy being
 * kept in two places and drifting.
 */
class Locations extends BaseCrudController
{
    protected string $modelClass = LocationModel::class;
    protected string $title      = 'Tourist locations';
    protected string $singular   = 'Location';
    protected string $route      = 'locations';
    protected string $active     = 'locations';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Place', 'type' => 'locale'],
        ['name' => 'distance_km', 'label' => 'Distance (km)'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Place', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'permit_empty|alpha_dash|max_length[191]', 'help' => 'Optional. Lower-case, hyphenated'],
        ['name' => 'summary', 'label' => 'Short description', 'type' => 'locale_textarea', 'help' => 'The line that appears on the card'],
        ['name' => 'description', 'label' => 'Full description', 'type' => 'locale_textarea'],
        ['name' => 'image', 'label' => 'Photograph', 'type' => 'image', 'folder' => 'locations', 'help' => 'Landscape, at least 900×600'],
        ['name' => 'image_alt', 'label' => 'Photograph description', 'type' => 'locale', 'help' => 'What the picture shows, for readers who cannot see it. Leave blank if the picture adds nothing the text does not already say'],
        ['name' => 'distance_km', 'label' => 'Distance from the hotel (km)', 'type' => 'number', 'rules' => 'permit_empty|decimal'],
        ['name' => 'travel_time', 'label' => 'How long it takes', 'rules' => 'permit_empty|max_length[60]', 'placeholder' => '45 minutes by road'],
        ['name' => 'url', 'label' => 'Reference link', 'rules' => 'permit_empty|max_length[255]', 'help' => 'Optional — a tourism board page, for instance. Opens in a new tab'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'help' => 'Low numbers come first'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Visible', 'draft' => 'Hidden']],
    ];
}
