<?php

namespace Modules\Admin\Controllers;

use Modules\Cms\Models\RoomModel;

/**
 * Rooms.
 *
 * These were blocks inside the home page's Rooms section, which made adding a
 * third one a matter of adding a block to the right section of the right page
 * and typing its JSON. Here a room is a record: it can be created, reordered,
 * taken off the site for a season and put back, and it appears wherever rooms
 * are listed without being written twice.
 */
class Rooms extends BaseCrudController
{
    protected string $modelClass = RoomModel::class;
    protected string $title      = 'Rooms';
    protected string $singular   = 'Room';
    protected string $route      = 'rooms';
    protected string $active     = 'rooms';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Room', 'type' => 'locale'],
        ['name' => 'bed', 'label' => 'Bed'],
        ['name' => 'max_guests', 'label' => 'Sleeps'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Room name', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'permit_empty|alpha_dash|max_length[191]', 'help' => 'Optional. Lower-case, hyphenated — used if this room ever gets a page of its own'],
        ['name' => 'summary', 'label' => 'Short description', 'type' => 'locale_textarea', 'help' => 'The line that appears on the card. Two sentences at most'],
        ['name' => 'description', 'label' => 'Full description', 'type' => 'locale_textarea', 'help' => 'Shown where there is room for it. Falls back to the short description'],
        ['name' => 'image', 'label' => 'Photograph', 'type' => 'image', 'folder' => 'rooms', 'help' => 'Landscape, at least 900×600'],
        ['name' => 'gallery', 'label' => 'More photographs', 'type' => 'gallery', 'folder' => 'rooms'],
        ['name' => 'features', 'label' => 'What the room has', 'type' => 'list', 'item_label' => 'feature', 'help' => 'One per line — balcony, air conditioning, and so on'],
        ['name' => 'bed', 'label' => 'Bed', 'rules' => 'permit_empty|max_length[120]', 'placeholder' => 'Double'],
        ['name' => 'max_guests', 'label' => 'Sleeps', 'type' => 'number', 'rules' => 'permit_empty|is_natural'],
        ['name' => 'size_sqm', 'label' => 'Size (m²)', 'type' => 'number', 'rules' => 'permit_empty|is_natural'],
        ['name' => 'price_from', 'label' => 'From (per night)', 'type' => 'number', 'rules' => 'permit_empty|decimal', 'help' => 'Blank shows no price, which is the right answer when rates change by season'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'help' => 'Low numbers come first'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Visible', 'draft' => 'Hidden'], 'help' => 'Hidden keeps the room and its photographs but takes it off the site'],
    ];
}
