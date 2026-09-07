<?php

namespace Modules\Commerce\Models;

use CodeIgniter\Model;

class CartItemModel extends Model
{
    protected $table         = 'cart_items';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'cart_id', 'item_type', 'item_id', 'qty', 'unit_price_cents',
        'discount_cents', 'meta_json',
    ];
}
