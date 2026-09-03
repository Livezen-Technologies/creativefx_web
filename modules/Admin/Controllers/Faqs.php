<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\FaqModel;

/** Frequently asked questions (Clause 3.9 K). */
class Faqs extends BaseCrudController
{
    protected string $modelClass = FaqModel::class;
    protected string $title      = 'FAQs';
    protected string $singular   = 'Question';
    protected string $route      = 'faqs';
    protected string $active     = 'faqs';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'question', 'label' => 'Question', 'type' => 'locale'],
        ['name' => 'category', 'label' => 'Category', 'type' => 'badge'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'question', 'label' => 'Question', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'answer', 'label' => 'Answer', 'type' => 'locale_richtext', 'rules' => 'required'],
        ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => [
            'registration' => 'Registration',
            'subsidies'    => 'Subsidies',
            'fertilizer'   => 'Fertilizer',
            'societies'    => 'Societies',
            'training'     => 'Training',
            'website'      => 'This website',
            'general'      => 'General',
        ]],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
