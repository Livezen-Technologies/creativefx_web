<?php

namespace Modules\Admin\Controllers;

/**
 * Portfolio projects — one row per piece of client work on /{locale}/portfolio
 * and its detail page. The case-study fields are all optional: a project can be
 * a cover image and a title, and the detail view omits whatever is left empty.
 */
class PortfolioProjects extends BaseCrudController
{
    protected string $modelClass = 'Modules\Portfolio\Models\PortfolioProjectModel';
    protected string $title      = 'Portfolio Projects';
    protected string $singular   = 'Portfolio Project';
    protected string $route      = 'portfolio-projects';
    protected string $active     = 'portfolio-projects';
    protected string $orderBy    = 'project_date';
    protected string $orderDir   = 'DESC';

    protected array $listColumns = [
        ['name' => 'slug', 'label' => 'Slug'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'client', 'label' => 'Client'],
        ['name' => 'project_date', 'label' => 'Shot'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    protected array $fields = [
        ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => [], 'help' => 'Decides which filter pill the project appears under'],
        ['name' => 'slug', 'label' => 'Slug', 'rules' => 'required|alpha_dash|max_length[191]', 'help' => 'URL segment, e.g. ceylon-tea-exports-highland-harvest'],
        ['name' => 'title', 'label' => 'Title', 'type' => 'locale'],
        ['name' => 'client', 'label' => 'Client', 'help' => 'Named on the card and in the fact list, e.g. Ceylon Tea Exports'],
        ['name' => 'industry', 'label' => 'Industry', 'help' => 'e.g. Food & Beverage, Hospitality, Retail'],
        ['name' => 'service', 'label' => 'Service', 'help' => 'What we did, in words, e.g. Commercial film production'],
        ['name' => 'project_date', 'label' => 'Shoot date', 'rules' => 'permit_empty|valid_date[Y-m-d]', 'help' => 'YYYY-MM-DD. Newest first is the portfolio order, so this is worth filling in.'],
        ['name' => 'year', 'label' => 'Year', 'rules' => 'permit_empty|max_length[8]', 'help' => 'Shown instead of the full date when there is no exact day, e.g. 2026'],
        ['name' => 'excerpt', 'label' => 'Excerpt', 'type' => 'locale_textarea', 'help' => 'One or two sentences on the portfolio card'],
        ['name' => 'description', 'label' => 'Description', 'type' => 'locale_richtext', 'help' => 'The opening of the case study — what the client sells and what we made'],
        ['name' => 'challenge', 'label' => 'Challenge', 'type' => 'locale_richtext', 'compact' => true, 'help' => 'The brief in the client’s terms'],
        ['name' => 'approach', 'label' => 'Approach', 'type' => 'locale_richtext', 'compact' => true, 'help' => 'How the work was shot and finished — crew, days, kit, grade'],
        ['name' => 'results', 'label' => 'Results', 'type' => 'locale_richtext', 'compact' => true, 'help' => 'What it delivered, with numbers where we have them'],
        ['name' => 'cover_image', 'label' => 'Cover image', 'type' => 'image', 'folder' => 'portfolio', 'help' => 'The card thumbnail and the top of the detail page'],
        ['name' => 'video_url', 'label' => 'Hero film', 'help' => 'A /media path plays inline; a YouTube or Vimeo link is embedded. Anything else is ignored and the cover image runs instead.'],
        ['name' => 'gallery', 'label' => 'Gallery', 'type' => 'textarea', 'help' => 'JSON list of frames: [{"src":"/media/portfolio/frame-01.webp","caption":{"en":"Lighting the grading room"}}]. Frames whose file is missing are skipped.'],
        ['name' => 'featured', 'label' => 'Featured', 'type' => 'checkbox', 'help' => 'Eligible for the highlights strip on the home and service pages'],
        ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|is_natural', 'help' => 'Orders the featured highlights only; the portfolio index stays newest first'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft']],
        ['name' => 'meta_title', 'label' => 'SEO meta title', 'type' => 'locale', 'help' => 'Overrides the browser-tab / search-result title'],
        ['name' => 'meta_description', 'label' => 'Meta description', 'type' => 'locale'],
    ];

    protected function renderForm(?array $row)
    {
        helper('norlanka');

        // Category options are data-driven, so they're resolved per request.
        foreach ($this->fields as &$field) {
            if ($field['name'] === 'category_id') {
                $options = ['' => '— none —'];
                foreach (model('Modules\Portfolio\Models\PortfolioCategoryModel')->orderBy('sort_order')->findAll() as $cat) {
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

        // Normalise the optional columns so an empty input stores NULL rather
        // than '' — a strict-mode DATE column rejects the empty string, and an
        // unfiled project needs a real NULL for the ON DELETE SET NULL key.
        $data['category_id']  = $data['category_id'] !== '' && $data['category_id'] !== null ? (int) $data['category_id'] : null;
        $data['project_date'] = ! empty($data['project_date']) ? $data['project_date'] : null;
        $data['gallery']      = trim((string) $data['gallery']) !== '' ? $data['gallery'] : null;
        $data['sort_order']   = (int) $data['sort_order'];

        return $data;
    }
}
