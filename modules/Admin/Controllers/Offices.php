<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\OfficeModel;

/**
 * Head office, regional offices, sub offices and the training centre
 * (Clause 3.9 E.d and J.a).
 *
 * The coordinates are here because the map on the Contact page reads them: an
 * office with no coordinates is listed but not pinned, which is the right
 * behaviour while somebody is still finding the exact position.
 */
class Offices extends BaseCrudController
{
    protected string $modelClass = OfficeModel::class;
    protected string $title      = 'Offices';
    protected string $singular   = 'Office';
    protected string $route      = 'offices';
    protected string $active     = 'offices';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Office', 'type' => 'locale'],
        ['name' => 'kind', 'label' => 'Type', 'type' => 'badge'],
        ['name' => 'district', 'label' => 'District'],
        ['name' => 'phone', 'label' => 'Telephone'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Office name', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'slug', 'label' => 'Address', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'kind', 'label' => 'Type', 'type' => 'select', 'options' => [
            'head'     => 'Head Office',
            'regional' => 'Regional Office',
            'sub'      => 'Sub Office',
            'centre'   => 'Training Centre',
        ]],
        ['name' => 'district', 'label' => 'District', 'rules' => 'permit_empty|max_length[64]'],
        ['name' => 'province', 'label' => 'Province', 'rules' => 'permit_empty|max_length[64]'],
        ['name' => 'address', 'label' => 'Postal address', 'type' => 'locale_textarea'],
        ['name' => 'phone', 'label' => 'Telephone', 'rules' => 'permit_empty|max_length[64]'],
        ['name' => 'fax', 'label' => 'Fax', 'rules' => 'permit_empty|max_length[64]'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'rules' => 'permit_empty|valid_email|max_length[128]'],
        ['name' => 'latitude', 'label' => 'Latitude', 'rules' => 'permit_empty|decimal', 'placeholder' => '6.8964', 'help' => 'Read it off a map. Blank leaves the office off the map but still in the list'],
        ['name' => 'longitude', 'label' => 'Longitude', 'rules' => 'permit_empty|decimal', 'placeholder' => '79.9187'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Hidden']],
    ];
}
