<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
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

        return view('Modules\Site\Views\home\index', [
            'page'     => $page,
            'sections' => $sections,
            'video'    => $video,
            'title'    => 'Norlanka — Responsible Apparel Manufacturing',
        ]);
    }
}
