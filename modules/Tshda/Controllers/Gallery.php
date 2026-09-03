<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Media\Models\MediaModel;
use Modules\Video\Models\VideoModel;

/**
 * The media gallery (Clause 3.9 I): a photo gallery with albums and a
 * lightbox, and a video gallery supporting embedded and self-hosted video.
 * News and event detail pages — the third part of I — are the newsroom's.
 */
class Gallery extends BaseController
{
    public function photos(?string $locale = null)
    {
        helper('norlanka');

        $model  = new MediaModel();
        $album  = trim((string) $this->request->getGet('album'));
        $albums = [];
        $images = [];

        try {
            // Albums are the media library's folders. Using the folder rather
            // than a separate album table means an officer who files a
            // photograph has already put it in an album.
            $rows = $model->where('mime_type LIKE', 'image/%')->orderBy('created_at', 'DESC')->findAll(500);
            foreach ($rows as $row) {
                $folder = trim((string) ($row['folder'] ?? '')) ?: 'general';
                $albums[$folder] = ($albums[$folder] ?? 0) + 1;
                if ($album === '' || $album === $folder) {
                    $images[] = $row;
                }
            }
            ksort($albums);
        } catch (\Throwable $e) {
            $albums = $images = [];
        }

        return view('Modules\Tshda\Views\gallery\photos', [
            'images'          => $images,
            'albums'          => $albums,
            'album'           => $album,
            'title'           => lang('Site.gallery.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.gallery.meta'),
        ]);
    }

    public function videos(?string $locale = null)
    {
        helper('norlanka');

        $videos = [];
        try {
            $videos = (new VideoModel())->where('status', 'published')->orderBy('id', 'DESC')->findAll(60);
        } catch (\Throwable $e) {
            $videos = [];
        }

        return view('Modules\Tshda\Views\gallery\videos', [
            'videos'          => $videos,
            'title'           => lang('Site.gallery.videos_title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.gallery.videos_meta'),
        ]);
    }
}
