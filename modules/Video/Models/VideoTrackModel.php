<?php

namespace Modules\Video\Models;

use CodeIgniter\Model;

class VideoTrackModel extends Model
{
    protected $table         = 'video_tracks';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['video_id', 'locale', 'label', 'audio_path', 'kind', 'is_default', 'sort_order'];
}
