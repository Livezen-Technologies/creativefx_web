<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\OfficeModel;
use Modules\Tshda\Models\StaffModel;

/**
 * The staff directory (Clause 3.9 E.d and J.b).
 *
 * A person's name is a plain field and their designation is translatable,
 * which is the right way round: a name is a name in every language, and
 * "Regional Manager" is not.
 */
class Staff extends BaseCrudController
{
    protected string $modelClass = StaffModel::class;
    protected string $title      = 'Staff directory';
    protected string $singular   = 'Officer';
    protected string $route      = 'staff';
    protected string $active     = 'staff';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Name'],
        ['name' => 'designation', 'label' => 'Designation', 'type' => 'locale'],
        ['name' => 'is_senior', 'label' => 'Senior'],
        ['name' => 'phone', 'label' => 'Telephone'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    public function __construct()
    {
        // The office list is built from the offices table rather than typed
        // twice, so an office added on the other screen is immediately
        // selectable here — and cannot drift out of step with it.
        $options = ['' => '— none —'];
        try {
            helper('norlanka');
            foreach ((new OfficeModel())->live() as $office) {
                $options[(string) $office['id']] = t_field($office['name']);
            }
        } catch (\Throwable $e) {
            // No table yet: the field renders with just the empty option.
        }

        $this->fields = [
            ['name' => 'name', 'label' => 'Name', 'rules' => 'required|max_length[191]', 'help' => 'The officer’s name as it should be published. “To be confirmed” is an honest placeholder while a post is vacant'],
            ['name' => 'designation', 'label' => 'Designation', 'type' => 'locale', 'rules' => 'required'],
            ['name' => 'office_id', 'label' => 'Office', 'type' => 'select', 'options' => $options],
            ['name' => 'division', 'label' => 'Division', 'type' => 'locale', 'help' => 'Used by the division filter on the public directory'],
            ['name' => 'subject_area', 'label' => 'Subject area', 'type' => 'locale', 'help' => 'What a member of the public would approach this officer about'],
            ['name' => 'phone', 'label' => 'Telephone', 'rules' => 'permit_empty|max_length[64]'],
            ['name' => 'mobile', 'label' => 'Mobile', 'rules' => 'permit_empty|max_length[64]'],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => 'permit_empty|valid_email|max_length[128]'],
            ['name' => 'photo', 'label' => 'Photograph', 'type' => 'image', 'folder' => 'staff'],
            ['name' => 'is_senior', 'label' => 'Senior management', 'type' => 'checkbox', 'help' => 'Senior officers are listed first and appear on the About Us pages'],
            ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Hidden']],
        ];
    }
}
