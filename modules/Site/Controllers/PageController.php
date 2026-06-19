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

        return view('Modules\Site\Views\cms\page', [
            'page'  => $page,
            'title' => t_field($page['meta_title'] ?? $page['title'] ?? ''),
        ]);
    }
}
