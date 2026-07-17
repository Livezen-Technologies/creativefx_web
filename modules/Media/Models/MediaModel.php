<?php

namespace Modules\Media\Models;

use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table         = 'media_library';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'disk', 'path', 'url', 'mime_type', 'size_bytes', 'width', 'height', 'alt', 'folder', 'tags', 'uploaded_by',
    ];
}
