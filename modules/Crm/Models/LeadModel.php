<?php

namespace Modules\Crm\Models;

use CodeIgniter\Model;

class LeadModel extends Model
{
    protected $table         = 'leads';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'email', 'company', 'country', 'phone', 'interest', 'quantity', 'message', 'status', 'source'];
}
