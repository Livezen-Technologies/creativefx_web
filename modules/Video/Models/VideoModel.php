<?php

namespace Modules\Video\Models;

use CodeIgniter\Model;

class VideoModel extends Model
{
    protected $table         = 'videos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'key', 'title', 'poster_path', 'src_path', 'src_path_webm', 'hls_manifest',
        'duration_seconds', 'is_muted_loop', 'status',
    ];

    /**
     * Fetch a video by its stable key with its audio tracks + subtitles attached.
     */
    public function findByKeyWithTracks(string $key): ?array
    {
        $video = $this->where('key', $key)->where('status', 'published')->first();
        if ($video === null) {
            return null;
        }

        $video['tracks'] = (new VideoTrackModel())
            ->where('video_id', $video['id'])
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $video['subtitles'] = (new VideoSubtitleModel())
            ->where('video_id', $video['id'])
            ->findAll();

        return $video;
    }
}
