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

        helper('norlanka');

        return view('Modules\Site\Views\home\index', [
            'page'            => $page,
            'sections'        => $sections,
            'video'           => $video,
            // The home page has a pages row like every other page, and the
            // seeder writes its title and description there. Composing them
            // here instead meant maintaining the same two strings twice: the
            // title came out as the site name followed by the hero eyebrow —
            // "Kukuleganga Giants Forest — Kukuleganga Giants Forest" — and the
            // description still named a section this page no longer has. Read
            // the row, and fall back only when there is nothing in it.
            'title'           => t_field($page['meta_title'] ?? []) ?: setting('site_name', ''),
            'metaDescription' => t_field($page['meta_description'] ?? []) ?: '',
        ]);
    }
}
