<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\ProgrammeModel;

/**
 * The Hantana National Training Centre's programme calendar (Clause 3.1.IV).
 *
 * `booked` is shown but not editable: it is the count of confirmed places, and
 * it is what closes a programme automatically when it fills. Letting it be
 * typed would let a programme be reopened past its capacity by a keystroke, and
 * silently — the applicant would be told there was room when there was not.
 */
class Programmes extends BaseCrudController
{
    protected string $modelClass = ProgrammeModel::class;
    protected string $title      = 'Training programmes';
    protected string $singular   = 'Programme';
    protected string $route      = 'programmes';
    protected string $active     = 'hantana';
    protected string $orderBy    = 'starts_on';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Programme', 'type' => 'locale'],
        ['name' => 'starts_on', 'label' => 'Starts'],
        ['name' => 'closes_on', 'label' => 'Applications close'],
        ['name' => 'capacity', 'label' => 'Capacity'],
        ['name' => 'booked', 'label' => 'Confirmed'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $extraActions = [
        ['label' => 'Applications', 'path' => 'admin/bookings?programme={id}'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Programme', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'slug', 'label' => 'Address', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea', 'rules' => 'required'],
        ['name' => 'description', 'label' => 'Full description', 'type' => 'locale_richtext'],
        ['name' => 'audience', 'label' => 'Who it is for', 'type' => 'locale'],
        ['name' => 'starts_on', 'label' => 'Starts', 'placeholder' => 'YYYY-MM-DD'],
        ['name' => 'ends_on', 'label' => 'Ends', 'placeholder' => 'YYYY-MM-DD'],
        ['name' => 'closes_on', 'label' => 'Applications close', 'placeholder' => 'YYYY-MM-DD', 'help' => 'The programme stops accepting applications on this date, without anybody having to remember'],
        ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number', 'rules' => 'permit_empty|is_natural', 'help' => 'Places. Zero means no limit — applications stay open until the closing date'],
        ['name' => 'booked', 'label' => 'Confirmed places', 'type' => 'static', 'help' => 'Counted from confirmed applications. Confirm or decline them on the Applications screen'],
        ['name' => 'residential', 'label' => 'Residential', 'type' => 'checkbox'],
        ['name' => 'fee', 'label' => 'Fee', 'type' => 'locale'],
        ['name' => 'venue', 'label' => 'Venue', 'type' => 'locale'],
        ['name' => 'image', 'label' => 'Photograph', 'type' => 'image', 'folder' => 'hantana'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
