<?php

namespace Modules\Admin\Controllers;

class Contacts extends BaseCrudController
{
    protected string $modelClass = 'Modules\Crm\Models\ContactModel';
    protected string $title      = 'Contact Inbox';
    protected string $singular   = 'Message';
    protected string $route      = 'contacts';
    protected string $active     = 'contacts';
    protected string $orderBy    = 'id';
    protected string $orderDir   = 'DESC';
    protected bool $canCreate    = false;

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Name'],
        ['name' => 'email', 'label' => 'Email'],
        ['name' => 'subject', 'label' => 'Subject'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
        ['name' => 'created_at', 'label' => 'Received'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Name', 'type' => 'static'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'static'],
        ['name' => 'subject', 'label' => 'Subject', 'type' => 'static'],
        ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'readonly' => true],
        ['name' => 'locale', 'label' => 'Locale', 'type' => 'static'],
        ['name' => 'source', 'label' => 'Source', 'type' => 'static'],
        ['name' => 'created_at', 'label' => 'Received', 'type' => 'static'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived']],
    ];
}
