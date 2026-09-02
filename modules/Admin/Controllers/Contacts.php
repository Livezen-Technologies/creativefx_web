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
        // A room request carries its arrival date, and the whole point of a
        // request inbox is being able to see what is coming without opening
        // every row. Empty for a plain contact message.
        ['name' => 'check_in', 'label' => 'Arriving'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
        ['name' => 'created_at', 'label' => 'Received'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Name', 'type' => 'static'],
        ['name' => 'email', 'label' => 'Email', 'type' => 'static'],
        // Phone was collected by the contact form from the beginning and shown
        // nowhere, so the one field a guest most expects to be rung back on was
        // invisible to the people meant to ring them.
        ['name' => 'phone', 'label' => 'Phone', 'type' => 'static'],
        ['name' => 'subject', 'label' => 'Subject', 'type' => 'static'],
        ['name' => 'room_type', 'label' => 'Room', 'type' => 'static'],
        ['name' => 'check_in', 'label' => 'Arriving', 'type' => 'static'],
        ['name' => 'check_out', 'label' => 'Leaving', 'type' => 'static'],
        ['name' => 'adults', 'label' => 'Adults', 'type' => 'static'],
        ['name' => 'children', 'label' => 'Children', 'type' => 'static'],
        ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'readonly' => true],
        ['name' => 'locale', 'label' => 'Locale', 'type' => 'static'],
        ['name' => 'source', 'label' => 'Source', 'type' => 'static'],
        ['name' => 'created_at', 'label' => 'Received', 'type' => 'static'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied', 'archived' => 'Archived']],
    ];
}
