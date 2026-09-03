<?php

namespace Modules\Cms\Models;

use CodeIgniter\Model;

class LocationModel extends Model
{
    protected $table          = 'locations';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'slug', 'name', 'summary', 'description', 'image', 'image_alt',
        'distance_km', 'travel_time', 'url', 'sort_order', 'status',
    ];

    /** The places a visitor can see, in the order an editor put them in. */
    public function published(): array
    {
        return $this->where('status', 'published')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
