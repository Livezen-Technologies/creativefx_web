<?php

namespace Modules\Admin\Controllers;

class EsgMetrics extends BaseCrudController
{
    protected string $modelClass = 'Modules\Esg\Models\EsgMetricModel';
    protected string $title      = 'ESG Metrics';
    protected string $singular   = 'Metric';
    protected string $route      = 'esg-metrics';
    protected string $active     = 'esg';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'key', 'label' => 'Key'],
        ['name' => 'label', 'label' => 'Label', 'type' => 'locale'],
        ['name' => 'value', 'label' => 'Value'],
        ['name' => 'unit', 'label' => 'Unit'],
        ['name' => 'pillar', 'label' => 'Pillar', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'key', 'label' => 'Key', 'rules' => 'required|max_length[64]'],
        ['name' => 'label', 'label' => 'Label', 'type' => 'locale'],
        ['name' => 'value', 'label' => 'Value', 'help' => 'e.g. 32 or 120k'],
        ['name' => 'unit', 'label' => 'Unit', 'help' => 'e.g. %'],
        ['name' => 'pillar', 'label' => 'Pillar', 'type' => 'select', 'options' => ['sourcing' => 'Responsible Sourcing', 'design' => 'Design', 'innovation' => 'Innovation']],
        ['name' => 'year', 'label' => 'Year', 'type' => 'number'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
    ];
}
