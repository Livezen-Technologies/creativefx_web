<?php

namespace Modules\Portfolio\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Portfolio\Models\PortfolioCategoryModel;
use Modules\Portfolio\Models\PortfolioProjectModel;

/**
 * Public portfolio: a filterable grid of client work plus the project case
 * study. The ?category=slug filter is a real, linkable state — an unknown slug
 * 404s rather than silently showing everything.
 */
class Portfolio extends BaseController
{
    private const HERO_IMAGE = '/media/placeholders/hero-portfolio.svg';

    public function index(?string $locale = null)
    {
        helper('norlanka');

        $categories = (new PortfolioCategoryModel())->published();

        $activeCategory = null;
        $catSlug        = trim((string) $this->request->getGet('category'));
        if ($catSlug !== '') {
            foreach ($categories as $c) {
                if ($c['slug'] === $catSlug) {
                    $activeCategory = $c;
                    break;
                }
            }
            if ($activeCategory === null) {
                throw PageNotFoundException::forPageNotFound();
            }
        }

        // The whole published grid goes to the view even when a category is
        // active: the view hides the cards outside the filter server-side, so
        // the ?category links work with JavaScript off, and Alpine re-shows
        // them instantly when it is on. Portfolios are curated and small, so
        // there is nothing to paginate.
        $projects = (new PortfolioProjectModel())->published()->findAll();

        $siteName = setting('site_name', 'CreativeFX');
        $catName  = $activeCategory !== null ? t_field($activeCategory['name']) : '';

        return view('Modules\Portfolio\Views\index', [
            'projects'        => $projects,
            'categories'      => $categories,
            'activeCategory'  => $activeCategory,
            'heroImage'       => self::HERO_IMAGE,
            'title'           => ($catName !== '' ? $catName . ' — Portfolio' : 'Portfolio') . ' — ' . $siteName,
            'metaDescription' => $catName !== ''
                ? $catName . ' projects from the CreativeFX portfolio — films, photography and campaigns produced for brands across Sri Lanka.'
                : 'Selected work from CreativeFX: brand films, commercial photography, podcast series, live streams and performance campaigns produced in Colombo and across Sri Lanka.',
            'ogImage'         => self::HERO_IMAGE,
        ]);
    }

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $projectModel = new PortfolioProjectModel();
        $project      = $projectModel->findPublishedBySlug((string) $slug);
        if ($project === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $category = ! empty($project['category_id'])
            ? (new PortfolioCategoryModel())->find((int) $project['category_id'])
            : null;

        $siteName = setting('site_name', 'CreativeFX');

        return view('Modules\Portfolio\Views\show', [
            'project'         => $project,
            'category'        => $category,
            'related'         => $projectModel->related($project, 3),
            'title'           => t_field($project['meta_title'] ?: $project['title']) . ' — ' . $siteName,
            'metaDescription' => t_field($project['meta_description'] ?: $project['excerpt']),
            'ogImage'         => $project['cover_image'] ?? null,
        ]);
    }
}
