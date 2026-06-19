<?php

namespace Modules\Admin\Controllers;

class Jobs extends BaseCrudController
{
    protected string $modelClass = 'Modules\Careers\Models\JobModel';
    protected string $title      = 'Jobs';
    protected string $singular   = 'Job';
    protected string $route      = 'jobs';
    protected string $active     = 'jobs';
    protected string $orderBy    = 'id';
    protected string $orderDir   = 'DESC';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'department', 'label' => 'Department'],
        ['name' => 'country', 'label' => 'Country'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale'],
        ['name' => 'department', 'label' => 'Department'],
        ['name' => 'country', 'label' => 'Country'],
        ['name' => 'location', 'label' => 'Location'],
        ['name' => 'employment_type', 'label' => 'Employment type', 'type' => 'select', 'options' => ['full-time' => 'Full-time', 'part-time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship']],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['open' => 'Open', 'closed' => 'Closed', 'draft' => 'Draft']],
    ];
}
