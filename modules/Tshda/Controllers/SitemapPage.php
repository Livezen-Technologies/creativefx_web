<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Cms\Models\PageModel;
use Modules\Tshda\Models\ServiceModel;
use Modules\Tshda\Models\StatisticModel;

/**
 * The human-readable sitemap (Clause 3.9 L). The machine-readable XML one is
 * the Site module's, at /sitemap.xml, and is linked from here so a visitor who
 * wanted that finds it.
 *
 * Built from the same tables the navigation is built from, so a page added in
 * the console appears here without anybody remembering to add it — a sitemap
 * maintained by hand is a sitemap that is wrong.
 */
class SitemapPage extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        $pages = [];
        try {
            $pages = (new PageModel())->findAllPublished();
        } catch (\Throwable $e) {
            $pages = [];
        }

        $services = $stats = [];
        try {
            $services = (new ServiceModel())->live()->findAll();
            $stats    = (new StatisticModel())->live();
        } catch (\Throwable $e) {
            $services = $stats = [];
        }

        return view('Modules\Tshda\Views\sitemap_page', [
            'pages'           => $pages,
            'services'        => $services,
            'datasets'        => $stats,
            'nav'             => site_nav('header'),
            'title'           => lang('Site.sitemap.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.sitemap.meta'),
        ]);
    }
}
