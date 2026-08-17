<?php

namespace Modules\Admin\Controllers;

/**
 * The rental catalogue — one row per piece of kit that goes out on hire.
 *
 * Two switches, deliberately separate: `availability` is the day-to-day one a
 * coordinator flips when a camera is out or in the workshop (the card still
 * shows, with a pill saying so), while `status` decides whether the item is on
 * the site at all.
 */
class GearItems extends BaseCrudController
{
    protected string $modelClass = 'Modules\Gear\Models\GearItemModel';
    protected string $title      = 'Gear Items';
    protected string $singular   = 'Gear Item';
    protected string $route      = 'gear-items';
    protected string $active     = 'gear-items';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'rate_daily', 'label' => 'Daily rate'],
        ['name' => 'availability', 'label' => 'Availability', 'type' => 'badge'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => [], 'help' => 'The kit family this item is grouped under'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'Identifies the item on a quote request, e.g. sony-fx3-cinema-camera'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale', 'help' => 'Make and model as a renter would ask for it, e.g. Sony FX3 Cinema Camera'],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea', 'help' => 'One line on the card — what the kit is for, not a spec dump'],
        ['name' => 'specs', 'label' => 'Specs', 'type' => 'textarea', 'help' => 'JSON list: [{"label":{"en":"Sensor"},"value":"Full-frame 10.2MP Exmor R"}]. The card shows the first three.'],
        ['name' => 'image', 'label' => 'Photo', 'type' => 'image', 'folder' => 'gear', 'help' => 'Shown on the card; a missing file degrades to a tinted panel'],
        ['name' => 'rate_daily', 'label' => 'Daily rate', 'rules' => 'permit_empty|decimal', 'help' => 'Numbers only, e.g. 18000. Leave empty for kit quoted on request.'],
        ['name' => 'rate_weekly', 'label' => 'Weekly rate', 'rules' => 'permit_empty|decimal', 'help' => 'Numbers only, e.g. 72000'],
        ['name' => 'currency', 'label' => 'Currency', 'rules' => 'permit_empty|max_length[8]', 'help' => 'Shown beside the rate. LKR unless a client is quoted in something else.'],
        ['name' => 'availability', 'label' => 'Availability', 'type' => 'select', 'options' => [
            'available'   => 'Available',
            'booked'      => 'Booked',
            'maintenance' => 'In maintenance',
        ], 'help' => 'Booked and in-maintenance kit stays listed — the card says so and still takes a quote request for another date'],
        ['name' => 'featured', 'label' => 'Featured', 'type' => 'checkbox', 'help' => 'Eligible for the highlights strip on the home and service pages'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|is_natural', 'help' => 'Low numbers first within the category, after the featured kit'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];

    protected function renderForm(?array $row)
    {
        helper('norlanka');

        // Category options are data-driven, so they're resolved per request.
        foreach ($this->fields as &$field) {
            if ($field['name'] === 'category_id') {
                $options = ['' => '— none —'];
                foreach (model('Modules\Gear\Models\GearCategoryModel')->orderBy('sort_order')->findAll() as $cat) {
                    $options[$cat['id']] = t_field($cat['name'], 'en') . ' (' . $cat['slug'] . ')';
                }
                $field['options'] = $options;
            }
        }
        unset($field);

        return parent::renderForm($row);
    }

    protected function collect(): array
    {
        $data = parent::collect();

        // Normalise the optional columns so an empty input stores NULL rather
        // than '' — 'quoted on request' is a missing rate, not a zero one, and
        // unfiled kit needs a real NULL for the ON DELETE SET NULL key.
        $data['category_id'] = $data['category_id'] !== '' && $data['category_id'] !== null ? (int) $data['category_id'] : null;
        $data['rate_daily']  = trim((string) $data['rate_daily']) !== '' ? $data['rate_daily'] : null;
        $data['rate_weekly'] = trim((string) $data['rate_weekly']) !== '' ? $data['rate_weekly'] : null;
        $data['specs']       = trim((string) $data['specs']) !== '' ? $data['specs'] : null;
        $data['currency']    = trim((string) $data['currency']) !== '' ? trim((string) $data['currency']) : 'LKR';
        $data['sort_order']  = (int) $data['sort_order'];

        return $data;
    }
}
