<?php

namespace Modules\Admin\Controllers;

class Leads extends BaseCrudController
{
    protected string $modelClass = 'Modules\Crm\Models\LeadModel';
    protected string $title      = 'Leads';
    protected string $singular   = 'Lead';
    protected string $route      = 'leads';
    protected string $active     = 'leads';
    protected string $orderBy    = 'id';
    protected string $orderDir   = 'DESC';
    protected bool $canCreate    = false;

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Name'],
        ['name' => 'email', 'label' => 'Email'],
        ['name' => 'company', 'label' => 'Company'],
        ['name' => 'interest', 'label' => 'Interest'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Name', 'type' => 'static'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'static'],
        ['name' => 'company', 'label' => 'Company', 'type' => 'static'],
        ['name' => 'country', 'label' => 'Country', 'type' => 'static'],
        ['name' => 'interest', 'label' => 'Interest', 'type' => 'static'],
        ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'readonly' => true],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['new' => 'New', 'qualified' => 'Qualified', 'converted' => 'Converted', 'lost' => 'Lost']],
    ];
}
