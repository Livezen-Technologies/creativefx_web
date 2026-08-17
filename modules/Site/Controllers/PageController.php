<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Cms\Models\PageModel;

/**
 * Generic CMS page renderer: resolves a published page by slug and renders its
 * ordered sections/blocks. The home page is served by the Home controller.
 */
class PageController extends BaseController
{
    /**
     * A service page: /{locale}/services/{slug} is the CMS page stored under
     * the slug "services/{slug}". Routed separately because the CMS catch-all
     * only matches a single segment.
     */
    public function service(string $slug)
    {
        return $this->show('services/' . $slug);
    }

    public function show(string $slug)
    {
        $pageModel = new PageModel();
        $page      = $pageModel->findPublishedBySlug($slug);

        if ($page === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ((int) ($page['is_home'] ?? 0) === 1 || ($page['template'] ?? '') === 'home') {
            return redirect()->to(locale_url(''));
        }

        $page = $pageModel->withStructure($page);

        // Per-page colour scope: Our Impact renders in the ESG "Regenerate" green.
        $pageTheme = $slug === 'impact' ? 'theme-esg-green' : '';

        return view('Modules\Site\Views\cms\page', [
            'page'            => $page,
            'title'           => t_field($page['meta_title'] ?? $page['title'] ?? ''),
            'metaDescription' => t_field($page['meta_description'] ?? ''),
            'ogImage'         => $page['og_image'] ?? null,
            'pageTheme'       => $pageTheme,
        ]);
    }
}
