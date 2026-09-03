<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\NoticeModel;

/**
 * Priority notices (Clause 3.9 B.IV).
 *
 * The point of this screen is speed. A fertilizer issue notice, a subsidy
 * deadline or an emergency advisory has to be publishable in seconds, so the
 * only required fields are a title and a body — the window, the link and the
 * ordering are all optional, and a notice with no dates shows from the moment
 * it is saved until somebody takes it down.
 */
class Notices extends BaseCrudController
{
    protected string $modelClass = NoticeModel::class;
    protected string $title      = 'Priority notices';
    protected string $singular   = 'Notice';
    protected string $route      = 'notices';
    protected string $active     = 'notices';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Notice', 'type' => 'locale'],
        ['name' => 'severity', 'label' => 'Severity', 'type' => 'badge'],
        ['name' => 'starts_at', 'label' => 'From'],
        ['name' => 'ends_at', 'label' => 'Until'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Notice', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'body', 'label' => 'Details', 'type' => 'locale_textarea', 'help' => 'One or two sentences. The full story belongs in an announcement, linked below'],
        ['name' => 'severity', 'label' => 'Severity', 'type' => 'select', 'options' => ['notice' => 'Notice', 'urgent' => 'Urgent'], 'help' => 'Urgent notices sort to the top and are marked more strongly. Use it for matters that genuinely cannot wait'],
        ['name' => 'url', 'label' => 'Link', 'rules' => 'permit_empty|max_length[255]', 'placeholder' => 'announcements', 'help' => 'A page slug, or a full address. Leave blank for a notice with nothing to read'],
        ['name' => 'starts_at', 'label' => 'Show from', 'placeholder' => 'YYYY-MM-DD HH:MM:SS', 'help' => 'Blank means immediately'],
        ['name' => 'ends_at', 'label' => 'Show until', 'placeholder' => 'YYYY-MM-DD HH:MM:SS', 'help' => 'Blank means until it is taken down by hand. Setting this is how a deadline notice removes itself'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'help' => 'Low numbers come first, within a severity'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
