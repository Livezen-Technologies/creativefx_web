<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Launch-video manager: edit the background film path and the per-language
 * audio tracks + subtitle paths that power the Home experience.
 */
class Videos extends BaseController
{
    public function edit()
    {
        $video = model('Modules\Video\Models\VideoModel')->findByKeyWithTracks('home_launch');
        if ($video === null) {
            return redirect()->to(site_url('admin'))->with('error', 'Launch video not seeded.');
        }

        return view('Modules\Admin\Views\videos', [
            'title'  => 'Launch Video',
            'active' => 'videos',
            'video'  => $video,
        ]);
    }

    public function update()
    {
        $videoModel = model('Modules\Video\Models\VideoModel');
        $video      = $videoModel->where('key', 'home_launch')->first();
        if ($video === null) {
            return redirect()->to(site_url('admin'))->with('error', 'Launch video not found.');
        }

        $videoModel->update($video['id'], [
            'src_path'    => $this->request->getPost('src_path') ?: null,
            'poster_path' => $this->request->getPost('poster_path') ?: null,
        ]);

        $trackModel = model('Modules\Video\Models\VideoTrackModel');
        foreach (($this->request->getPost('tracks') ?? []) as $id => $t) {
            $trackModel->update((int) $id, [
                'label'      => $t['label'] ?? '',
                'audio_path' => $t['audio_path'] ?? '',
            ]);
        }

        $subModel = model('Modules\Video\Models\VideoSubtitleModel');
        foreach (($this->request->getPost('subs') ?? []) as $id => $s) {
            $subModel->update((int) $id, ['vtt_path' => $s['vtt_path'] ?? '']);
        }

        return redirect()->to(site_url('admin/videos'))->with('message', 'Launch video updated.');
    }
}
