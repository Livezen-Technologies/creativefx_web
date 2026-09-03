<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Models\DiscussionTopicModel;

/**
 * Discussion topics (Clause 3.9 B.V, Clause 3.14): threads opened and closed by
 * the Authority.
 *
 * "Current topic" is what the home page asks about, and only one should carry
 * it at a time — the model reads the most recent, so an older one left flagged
 * is harmless rather than ambiguous.
 */
class DiscussionTopics extends BaseCrudController
{
    protected string $modelClass = DiscussionTopicModel::class;
    protected string $title      = 'Discussion topics';
    protected string $singular   = 'Topic';
    protected string $route      = 'discussion-topics';
    protected string $active     = 'discussion';
    protected string $orderBy    = 'id';
    protected string $orderDir   = 'DESC';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Topic', 'type' => 'locale'],
        ['name' => 'is_current', 'label' => 'On home page'],
        ['name' => 'closes_at', 'label' => 'Closes'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $extraActions = [
        ['label' => 'Comments', 'path' => 'admin/comments?topic={id}'],
    ];

    protected array $fields = [
        ['name' => 'title', 'label' => 'Topic', 'type' => 'locale', 'rules' => 'required'],
        ['name' => 'slug', 'label' => 'Address', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'body', 'label' => 'What you are asking', 'type' => 'locale_richtext', 'rules' => 'required', 'help' => 'Say what the Authority wants to hear about and who will read it'],
        ['name' => 'opens_at', 'label' => 'Opens', 'placeholder' => 'YYYY-MM-DD HH:MM:SS'],
        ['name' => 'closes_at', 'label' => 'Closes', 'placeholder' => 'YYYY-MM-DD HH:MM:SS', 'help' => 'Comments stop being accepted on this date'],
        ['name' => 'is_current', 'label' => 'Show on the home page', 'type' => 'checkbox'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
