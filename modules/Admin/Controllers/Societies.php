<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\SocietyModel;

/** The register of Tea Smallholder Development Societies (Clause 3.9 E.b). */
class Societies extends BaseCrudController
{
    protected string $modelClass = SocietyModel::class;
    protected string $title      = 'Societies';
    protected string $singular   = 'Society';
    protected string $route      = 'societies';
    protected string $active     = 'societies';
    protected string $orderBy    = 'name';

    protected array $listColumns = [
        ['name' => 'registration', 'label' => 'Registration'],
        ['name' => 'name', 'label' => 'Society'],
        ['name' => 'district', 'label' => 'District'],
        ['name' => 'members', 'label' => 'Members'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'registration', 'label' => 'Registration number', 'rules' => 'required|max_length[48]'],
        ['name' => 'name', 'label' => 'Society name', 'rules' => 'required|max_length[191]'],
        ['name' => 'kind', 'label' => 'Type', 'type' => 'select', 'options' => [
            'tsds'       => 'Tea Smallholder Development Society',
            'coop'       => 'Cooperative',
            'teashakthi' => 'Tea Shakthi',
        ]],
        ['name' => 'district', 'label' => 'District', 'rules' => 'permit_empty|max_length[64]'],
        ['name' => 'division', 'label' => 'Division / range', 'rules' => 'permit_empty|max_length[64]'],
        ['name' => 'members', 'label' => 'Members', 'type' => 'number', 'rules' => 'permit_empty|is_natural'],
        ['name' => 'secretary', 'label' => 'Secretary', 'rules' => 'permit_empty|max_length[191]'],
        ['name' => 'phone', 'label' => 'Telephone', 'rules' => 'permit_empty|max_length[64]'],
        ['name' => 'registered_on', 'label' => 'Registered on', 'placeholder' => 'YYYY-MM-DD'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Hidden']],
    ];
}
