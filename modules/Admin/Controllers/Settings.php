<?php

namespace Modules\Admin\Controllers;

class Settings extends BaseCrudController
{
    protected string $modelClass = 'Modules\Core\Models\SettingModel';
    protected string $title      = 'Settings';
    protected string $singular   = 'Setting';
    protected string $route      = 'settings';
    protected string $active     = 'settings';
    protected string $orderBy    = 'group';

    protected array $listColumns = [
        ['name' => 'group', 'label' => 'Group'],
        ['name' => 'key', 'label' => 'Key'],
        ['name' => 'value', 'label' => 'Value'],
    ];

    protected array $fields = [
        ['name' => 'group', 'label' => 'Group', 'rules' => 'required|max_length[64]'],
        ['name' => 'key', 'label' => 'Key', 'rules' => 'required|max_length[128]'],
        ['name' => 'value', 'label' => 'Value', 'type' => 'textarea'],
        ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => ['string' => 'string', 'json' => 'json', 'bool' => 'bool', 'int' => 'int']],
        ['name' => 'is_public', 'label' => 'Public', 'type' => 'checkbox', 'help' => 'Exposed to the front-end'],
    ];
}
