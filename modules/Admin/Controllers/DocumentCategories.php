<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\DocumentCategoryModel;

/** The eight download categories Clause 3.9 H names. */
class DocumentCategories extends BaseCrudController
{
    protected string $modelClass = DocumentCategoryModel::class;
    protected string $title      = 'Download categories';
    protected string $singular   = 'Category';
    protected string $route      = 'document-categories';
    protected string $active     = 'document-categories';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Category', 'type' => 'locale'],
        ['name' => 'slug', 'label' => 'Address'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Category', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'slug', 'label' => 'Address', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
