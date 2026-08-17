<?php

namespace Modules\Admin\Controllers;

/**
 * Portfolio categories: the discipline a project is filed under. They are the
 * filter pills on /{locale}/portfolio, so the slug is part of a public URL —
 * rename the name freely, leave the slug alone.
 */
class PortfolioCategories extends BaseCrudController
{
    protected string $modelClass = 'Modules\Portfolio\Models\PortfolioCategoryModel';
    protected string $title      = 'Portfolio Categories';
    protected string $singular   = 'Portfolio Category';
    protected string $route      = 'portfolio-categories';
    protected string $active     = 'portfolio-categories';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'Used in the portfolio filter URL, e.g. live-streaming'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale', 'help' => 'Label on the filter pill, e.g. Live Streaming'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|is_natural', 'help' => 'Low numbers first, left to right along the filter row'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft'], 'help' => 'A draft category keeps its projects; the pill simply disappears'],
    ];

    protected function collect(): array
    {
        $data = parent::collect();
        // An empty number input posts '', which a strict-mode INT column rejects.
        $data['sort_order'] = (int) $data['sort_order'];

        return $data;
    }
}
