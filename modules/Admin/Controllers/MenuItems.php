<?php

namespace Modules\Admin\Controllers;

use Modules\Cms\Models\MenuItemModel;

/**
 * Header and footer navigation.
 *
 * Both menus in one screen, filtered by location, because they are the same
 * kind of thing and keeping them apart is how the two lists drifted the last
 * time. Order is a number rather than drag-and-drop: it survives a page reload,
 * works without JavaScript, and is the one thing an editor needs to be able to
 * do from a phone.
 */
class MenuItems extends BaseCrudController
{
    protected string $modelClass = MenuItemModel::class;
    protected string $title      = 'Navigation menus';
    protected string $singular   = 'Menu item';
    protected string $route      = 'menu-items';
    protected string $active     = 'menu-items';
    protected string $orderBy    = 'location';

    protected array $listColumns = [
        ['name' => 'location', 'label' => 'Menu'],
        ['name' => 'label', 'label' => 'Label', 'type' => 'locale'],
        ['name' => 'url', 'label' => 'Links to'],
        ['name' => 'sort_order', 'label' => 'Order'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    public function __construct()
    {
        // Parents are offered by name, so nesting an item under another is a
        // choice from a list rather than an id typed from memory.
        $parents = ['' => '— Top level —'];
        foreach (model(MenuItemModel::class)->where('parent_id', null)->orderBy('location')->orderBy('sort_order')->findAll() as $row) {
            $label = json_decode((string) $row['label'], true);
            $text  = is_array($label) ? (string) reset($label) : (string) $row['label'];
            $parents[$row['id']] = ucfirst((string) $row['location']) . ' — ' . lang($text);
        }

        $this->fields = [
            ['name' => 'location', 'label' => 'Menu', 'type' => 'select', 'rules' => 'required|in_list[header,footer]', 'options' => ['header' => 'Header', 'footer' => 'Footer']],
            ['name' => 'label', 'label' => 'Label', 'type' => 'locale', 'help' => 'What the link says. A value like Site.nav.dining is looked up in the translations instead, which is how the seeded items stay translatable'],
            ['name' => 'url', 'label' => 'Links to', 'rules' => 'permit_empty|max_length[255]', 'help' => 'A page slug such as contact — the language prefix is added for you. A full https:// address, a path starting with /, or an #anchor is used exactly as typed'],
            ['name' => 'parent_id', 'label' => 'Inside dropdown', 'type' => 'select', 'options' => $parents, 'help' => 'Leave at top level for a normal link. Choosing a parent puts this item in that parent\'s dropdown'],
            ['name' => 'target', 'label' => 'Opens in', 'type' => 'select', 'options' => ['_self' => 'Same tab', '_blank' => 'New tab']],
            ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'help' => 'Low numbers come first, within each menu'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Visible', 'draft' => 'Hidden'], 'help' => 'Hiding a parent hides its dropdown with it'],
        ];
    }

    /** Blank parent means top level, and an empty string is not a null id. */
    protected function collect(): array
    {
        $data = parent::collect();

        if (array_key_exists('parent_id', $data)) {
            $data['parent_id'] = $data['parent_id'] === '' || $data['parent_id'] === '0'
                ? null
                : (int) $data['parent_id'];
        }

        return $data;
    }
}
