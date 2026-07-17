<?php

namespace Modules\Admin\Controllers;

class NewsPosts extends BaseCrudController
{
    protected string $modelClass = 'Modules\News\Models\NewsPostModel';
    protected string $title      = 'News Posts';
    protected string $singular   = 'News Post';
    protected string $route      = 'news-posts';
    protected string $active     = 'news-posts';
    protected string $orderBy    = 'published_at';
    protected string $orderDir   = 'DESC';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'published_at', 'label' => 'Published'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => []],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'URL segment, e.g. solar-expansion-2026'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'excerpt', 'label' => 'Excerpt', 'type' => 'locale_textarea', 'help' => 'Short summary shown on cards and in search results'],
        ['name' => 'body', 'label' => 'Body', 'type' => 'locale_textarea', 'help' => 'Plain text: blank line = new paragraph, "## " = heading, "- " = bullet list'],
        ['name' => 'image', 'label' => 'Featured image', 'help' => 'Path to the article image, e.g. /media/uploads/… (upload via Media)'],
        ['name' => 'tags', 'label' => 'Tags', 'help' => 'Comma-separated, e.g. Solar,Climate,Trincomalee'],
        ['name' => 'author', 'label' => 'Author', 'help' => 'e.g. Norlanka Communications'],
        ['name' => 'meta_title', 'label' => 'SEO meta title', 'type' => 'locale', 'help' => 'Overrides the browser-tab / search-result title'],
        ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'locale'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
        ['name' => 'published_at', 'label' => 'Publish date', 'help' => 'YYYY-MM-DD HH:MM:SS — a future time schedules the article'],
    ];

    protected function renderForm(?array $row)
    {
        helper('norlanka');

        // Category options are data-driven, so they're resolved per request.
        foreach ($this->fields as &$field) {
            if ($field['name'] === 'category_id') {
                $options = ['' => '— none —'];
                foreach (model('Modules\News\Models\NewsCategoryModel')->orderBy('sort_order')->findAll() as $cat) {
                    $options[$cat['id']] = t_field($cat['name'], 'en') . ' (' . $cat['slug'] . ')';
                }
                $field['options'] = $options;
            }
        }
        unset($field);

        return parent::renderForm($row);
    }

    protected function collect(): array
    {
        $data = parent::collect();
        // Normalise optional fields so empty inputs don't break queries.
        $data['category_id']  = $data['category_id'] !== '' && $data['category_id'] !== null ? (int) $data['category_id'] : null;
        $data['published_at'] = ! empty($data['published_at']) ? $data['published_at'] : null;
        return $data;
    }
}
