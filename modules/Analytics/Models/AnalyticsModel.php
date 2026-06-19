<?php

namespace Modules\Analytics\Models;

use CodeIgniter\Model;

class AnalyticsModel extends Model
{
    protected $table         = 'analytics';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';   // append-only event log
    protected $allowedFields = [
        'event', 'path', 'locale', 'session_id', 'user_id', 'referrer', 'user_agent', 'meta',
    ];
}
