<?php

namespace Modules\Cms\Models;

use CodeIgniter\Model;

class RoomModel extends Model
{
    protected $table          = 'rooms';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;
    protected $allowedFields  = [
        'slug', 'name', 'summary', 'description', 'image', 'gallery', 'features',
        'max_guests', 'bed', 'size_sqm', 'price_from', 'sort_order', 'status',
    ];

    /** The rooms a visitor can see, in the order an editor put them in. */
    public function published(): array
    {
        return $this->where('status', 'published')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }
}
