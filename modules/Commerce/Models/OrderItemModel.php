<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

class OrderItemModel extends Model
{
    protected $table          = 'order_items';
    protected $returnType     = 'array';
    protected $useTimestamps  = true;
    protected $updatedField   = '';
    protected $allowedFields  = [
        'order_id', 'item_type', 'item_id', 'course_id', 'session_id', 'bundle_id',
        'qty', 'title_snapshot', 'meta_snapshot_json', 'unit_price_cents',
        'discount_cents', 'tax_cents', 'total_cents', 'attendee_json',
    ];
}
