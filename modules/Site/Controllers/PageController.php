<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Cms\Libraries\PageSeo;
use Modules\Cms\Models\PageModel;

/**
 * Generic CMS page renderer: resolves a published page by slug and renders its
 * ordered sections/blocks. The home page is served by the Home controller.
 */
class PageController extends BaseController
{
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

        return view('Modules\Site\Views\cms\page', PageSeo::for($page)->toViewData(current_url()) + [
            'page'      => $page,
            'pageTheme' => $pageTheme,
        ]);
    }
}
