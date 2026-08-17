<?php

namespace Modules\Admin\Controllers;

/**
 * The services registry — the list that drives the header dropdown, the footer,
 * the Services overview grid and the related-service strip. Editing a row here
 * changes the wording everywhere at once; the long-form service page itself is
 * an ordinary CMS page under the slug 'services/<slug>', written in Pages.
 */
class Services extends BaseCrudController
{
    protected string $modelClass = 'Modules\Services\Models\ServiceModel';
    protected string $title      = 'Services';
    protected string $singular   = 'Service';
    protected string $route      = 'services';
    protected string $active     = 'services';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'URL segment and CMS page slug: /services/<slug>, e.g. podcast-studio. Changing it breaks existing links.'],
        ['name' => 'name', 'label' => 'Name', 'type' => 'locale', 'help' => 'Short label used in the navigation and on cards, e.g. Podcast Studio'],
        ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'locale', 'help' => 'One line under the name in the nav dropdown and on the card'],
        ['name' => 'summary', 'label' => 'Summary', 'type' => 'locale_textarea', 'help' => 'Two sentences for the Services overview grid'],
        ['name' => 'icon', 'label' => 'Icon', 'type' => 'select', 'options' => [
            ''          => '— none —',
            'camera'    => 'Camera — photography & videography',
            'mic'       => 'Microphone — podcast',
            'broadcast' => 'Broadcast — live streaming',
            'box'       => 'Box — gear renting',
            'megaphone' => 'Megaphone — social advertising',
            'chart'     => 'Chart — digital marketing',
        ], 'help' => 'Inline glyph shown on the service card when there is no card image'],
        ['name' => 'card_image', 'label' => 'Card image', 'type' => 'image', 'folder' => 'services', 'help' => 'Shown on the service card; the icon takes over if this is empty'],
        ['name' => 'hero_image', 'label' => 'Hero image', 'type' => 'image', 'folder' => 'services', 'help' => 'Banner at the top of the service page'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|is_natural', 'help' => 'Low numbers first, everywhere services are listed'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];

    protected function collect(): array
    {
        $data = parent::collect();
        // An empty number input posts '', which a strict-mode INT column rejects.
        $data['sort_order'] = (int) $data['sort_order'];

        return $data;
    }
}
