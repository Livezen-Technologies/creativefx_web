<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\OrgLinkModel;

/**
 * Links to related organisations and the government portals
 * (Clause 3.9 B.VII and Clause 3.10).
 *
 * The government group is not decoration: Clause 3.10 requires persistent links
 * to www.gov.lk and locallanguages.lk in the footer of every page, and the
 * footer renders whatever is in that group. Removing them all removes the
 * column, which is why the seeder puts them there and this screen exists to
 * add to them rather than to think about them again.
 */
class OrgLinks extends BaseCrudController
{
    protected string $modelClass = OrgLinkModel::class;
    protected string $title      = 'Organisation links';
    protected string $singular   = 'Link';
    protected string $route      = 'org-links';
    protected string $active     = 'org-links';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Organisation', 'type' => 'locale'],
        ['name' => 'group_key', 'label' => 'Group', 'type' => 'badge'],
        ['name' => 'url', 'label' => 'Address'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Organisation', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'url', 'label' => 'Address', 'rules' => 'required|max_length[255]', 'placeholder' => 'https://'],
        ['name' => 'group_key', 'label' => 'Where it appears', 'type' => 'select', 'options' => [
            'related'    => 'Related organisations (home page)',
            'government' => 'Government links (footer of every page)',
        ]],
        ['name' => 'logo', 'label' => 'Logo', 'type' => 'image', 'folder' => 'org-links'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Hidden']],
    ];
}
