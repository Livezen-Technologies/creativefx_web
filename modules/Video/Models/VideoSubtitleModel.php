<?php

namespace Modules\Video\Models;

use CodeIgniter\Model;

class VideoSubtitleModel extends Model
{
    protected $table         = 'video_subtitles';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['video_id', 'locale', 'label', 'vtt_path', 'is_default'];
}
