<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use Modules\Cms\Libraries\PageSeo;
use Modules\Cms\Models\PageModel;
use Modules\Video\Models\VideoModel;

class Home extends BaseController
{
    public function index(?string $locale = null)
    {
        $pageModel = new PageModel();
        $page      = $pageModel->findHome();

        // Build a section map keyed by section key for easy access in the view.
        $sections = [];
        if ($page !== null) {
            $page     = $pageModel->withStructure($page);
            $sections = array_column($page['sections'] ?? [], null, 'key');
        }

        $video = (new VideoModel())->findByKeyWithTracks('home_launch');

        helper('norlanka');

        // Title, description, keywords, canonical and the Open Graph fields all
        // come off the page row with one set of fallback rules, shared with the
        // CMS page controller. Composing them per-controller is how the home
        // page once titled itself with the site name twice over.
        $seo = PageSeo::for($page)->toViewData(current_url());

        return view('Modules\Site\Views\home\index', $seo + [
            'page'     => $page,
            'sections' => $sections,
            'video'    => $video,
        ]);
    }
}
