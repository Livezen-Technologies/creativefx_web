<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Cms\Models\PageModel;

/**
 * The home page.
 *
 * It renders through the ordinary CMS block pipeline — the same view every
 * other page uses — so the whole of the home page is editable in Admin ->
 * Pages -> Home, section by section, with no code change.
 *
 * (The Norlanka site instead rendered a bespoke nine-slide fullscreen deck
 * built around its launch film, at modules/Site/Views/home/index.php. That view
 * and its `hero`/`stat` block types are still on disk but nothing routes to
 * them; the CreativeFX home is a scrolling page.)
 */
class Home extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        $pageModel = new PageModel();
        $page      = $pageModel->findHome();

        if ($page === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $page = $pageModel->withStructure($page);

        return view('Modules\Site\Views\cms\page', [
            'page'            => $page,
            'title'           => t_field($page['meta_title'] ?? $page['title'] ?? ''),
            'metaDescription' => t_field($page['meta_description'] ?? ''),
            'ogImage'         => $page['og_image'] ?? null,
            'pageTheme'       => '',
        ]);
    }
}
