<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\StatisticModel;

/**
 * Published statistics (Clause 3.9 F).
 *
 * Columns and rows are typed as JSON, deliberately. A statistical table is
 * replaced wholesale when the next year's figures arrive, never edited cell by
 * cell, and a grid editor for that is a great deal of interface in exchange for
 * a workflow nobody uses. The Statistics screen shows the same data back as a
 * table and a chart, so a mistake is visible immediately.
 */
class Statistics extends BaseCrudController
{
    protected string $modelClass = StatisticModel::class;
    protected string $title      = 'Statistics';
    protected string $singular   = 'Dataset';
    protected string $route      = 'statistics';
    protected string $active     = 'statistics';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Dataset', 'type' => 'locale'],
        ['name' => 'chart', 'label' => 'Chart', 'type' => 'badge'],
        ['name' => 'period', 'label' => 'Period'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Dataset', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'slug', 'label' => 'Address', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'description', 'label' => 'What it shows', 'type' => 'locale_textarea'],
        ['name' => 'unit', 'label' => 'Unit', 'type' => 'locale', 'placeholder' => 'hectares'],
        ['name' => 'period', 'label' => 'Period', 'rules' => 'permit_empty|max_length[64]', 'placeholder' => '2021–2025'],
        ['name' => 'source', 'label' => 'Source', 'type' => 'locale'],
        ['name' => 'chart', 'label' => 'Presentation', 'type' => 'select', 'options' => [
            'bar'   => 'Bar chart and table',
            'line'  => 'Line series and table',
            'area'  => 'Area series and table',
            'table' => 'Table only',
        ], 'help' => 'The table is always shown — it is the accessible form of the same figures'],
        ['name' => 'columns', 'label' => 'Column headings', 'type' => 'textarea', 'rules' => 'permit_empty', 'help' => 'JSON: a list of locale maps, e.g. [{"en":"District"},{"en":"Holdings"}]'],
        ['name' => 'rows', 'label' => 'Rows', 'type' => 'textarea', 'rules' => 'permit_empty', 'help' => 'JSON: a list of lists, one per row, e.g. [["Galle",1200],["Matara",980]]. Use null for a figure not yet available'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
