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
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale_richtext', 'help' => 'The full role description — formatting, lists and links supported'],
        ['name' => 'department', 'label' => 'Department'],
        ['name' => 'country', 'label' => 'Country'],
        ['name' => 'location', 'label' => 'Location'],
        ['name' => 'employment_type', 'label' => 'Employment type', 'type' => 'select', 'options' => ['full-time' => 'Full-time', 'part-time' => 'Part-time', 'contract' => 'Contract', 'internship' => 'Internship']],
        ['name' => 'experience', 'label' => 'Experience', 'help' => 'e.g. "2–4 years in apparel merchandising"'],
        ['name' => 'qualifications', 'label' => 'Qualifications', 'type' => 'list', 'item_label' => 'qualification'],
        ['name' => 'skills', 'label' => 'Skills', 'type' => 'list', 'item_label' => 'skill'],
        ['name' => 'salary_range', 'label' => 'Salary range', 'help' => 'Optional, shown publicly if set'],
        ['name' => 'closes_at', 'label' => 'Application deadline', 'help' => 'Optional, YYYY-MM-DD HH:MM:SS — hidden from the site after this date'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['open' => 'Open', 'closed' => 'Closed', 'draft' => 'Draft']],
    ];
}
