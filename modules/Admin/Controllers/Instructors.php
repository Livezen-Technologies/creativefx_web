<?php

namespace Modules\Admin\Controllers;

/**
 * The faculty.
 *
 * `is_placeholder` is the field that matters. A profile carrying it describes
 * the team rather than a person, renders a note saying so on the public page,
 * and is deliberately excluded from the `Person` structured data — because
 * making a claim to a search engine about somebody who does not exist is not a
 * thing to do by accident. Clear it only when the row names a real trainer who
 * has agreed to appear.
 */
class Instructors extends BaseCrudController
{
    protected string $modelClass = 'Modules\Catalog\Models\InstructorModel';
    protected string $title      = 'Instructors';
    protected string $singular   = 'Instructor';
    protected string $route      = 'instructors';
    protected string $active     = 'instructors';
    protected string $orderBy    = 'sort_order';

    protected array $listColumns = [
        ['name' => 'name', 'label' => 'Name'],
        ['name' => 'headline', 'label' => 'Headline', 'type' => 'locale'],
        ['name' => 'is_placeholder', 'label' => 'Placeholder', 'type' => 'badge'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'name', 'label' => 'Name', 'rules' => 'required|max_length[128]'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]'],
        ['name' => 'headline', 'label' => 'Headline', 'type' => 'locale', 'help' => 'One line: what they teach and what they do'],
        ['name' => 'bio', 'label' => 'Biography', 'type' => 'locale_richtext'],
        ['name' => 'photo', 'label' => 'Photograph', 'type' => 'image'],
        ['name' => 'credentials_json', 'label' => 'Credentials', 'type' => 'list', 'item_label' => 'credential'],
        ['name' => 'links_json', 'label' => 'Links', 'type' => 'pairs', 'pair_labels' => ['Label', 'URL']],
        ['name' => 'is_placeholder', 'label' => 'Faculty placeholder, not a named person', 'type' => 'checkbox', 'help' => 'Leave ticked until this row names a real trainer who has agreed to appear. While it is ticked the profile says so and is left out of the structured data.'],
        ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
    ];
}
