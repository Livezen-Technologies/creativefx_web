<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\ServiceModel;

/**
 * The service catalogue (Clause 3.9 D and E).
 *
 * Eligibility, process, documents, division and contact point are separate
 * fields rather than one block of prose, because the clause asks for each of
 * them against every service — and because an officer filling in a form is
 * reminded of what is missing in a way that a blank textarea never manages.
 *
 * `window_open` is on this screen rather than derived from a date: an
 * application window opens and closes by circular, not by calendar, and the
 * person who receives the circular is the person who should be able to flip it.
 */
class Services extends BaseCrudController
{
    protected string $modelClass = ServiceModel::class;
    protected string $title      = 'Services';
    protected string $singular   = 'Service';
    protected string $route      = 'services';
    protected string $active     = 'services';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Service', 'type' => 'locale'],
        ['name' => 'area', 'label' => 'Area', 'type' => 'badge'],
        ['name' => 'audience', 'label' => 'For', 'type' => 'badge'],
        ['name' => 'window_open', 'label' => 'Open'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Service name', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'slug', 'label' => 'Address', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'Lower-case and hyphenated. This becomes /services/…, so changing it breaks links already sent out'],
        ['name' => 'area', 'label' => 'Functional area', 'type' => 'select', 'options' => [
            'land'      => 'Land development & extension',
            'societies' => 'Societies',
            'training'  => 'Training',
            'general'   => 'General',
        ]],
        ['name' => 'audience', 'label' => 'Who it is for', 'type' => 'select', 'options' => [
            'smallholder' => 'Tea smallholders',
            'society'     => 'Societies',
            'officer'     => 'Field officers',
            'supplier'    => 'Nurseries and suppliers',
            'jobseeker'   => 'Job seekers',
        ], 'help' => 'This decides which cluster the service appears in on the home page'],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea', 'rules' => 'required', 'help' => 'Two or three sentences. This is the card on the listing and the line under the heading'],
        ['name' => 'eligibility', 'label' => 'Who can apply', 'type' => 'locale_textarea'],
        ['name' => 'process', 'label' => 'How to apply', 'type' => 'list', 'item_label' => 'step', 'help' => 'One step per line, in order'],
        ['name' => 'documents', 'label' => 'What the applicant needs', 'type' => 'list', 'item_label' => 'document', 'help' => 'One document per line'],
        ['name' => 'fee', 'label' => 'Fee', 'type' => 'locale'],
        ['name' => 'duration', 'label' => 'How long it takes', 'type' => 'locale'],
        ['name' => 'division', 'label' => 'Responsible division', 'type' => 'locale'],
        ['name' => 'contact_point', 'label' => 'Where to ask', 'type' => 'locale'],
        ['name' => 'form_url', 'label' => 'Application form', 'rules' => 'permit_empty|max_length[255]', 'help' => 'A link to the form. Blank sends the applicant to the Application Forms category in Downloads'],
        ['name' => 'window_open', 'label' => 'Applications are open', 'type' => 'checkbox', 'help' => 'Off marks the service closed on every screen it appears on, without taking it off the site'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
