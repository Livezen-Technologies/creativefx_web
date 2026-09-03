<?php

namespace Modules\Tshda\Models;

use CodeIgniter\Model;

class OrgLinkModel extends Model
{
    protected $table         = 'org_links';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'url', 'logo', 'group_key', 'sort_order', 'status'];

    /** @return list<array> */
    public function live(?string $group = null): array
    {
        $this->where('status', 'published')->orderBy('sort_order', 'ASC');
        if ($group !== null) {
            $this->where('group_key', $group);
        }

        return $this->findAll();
    }
}
