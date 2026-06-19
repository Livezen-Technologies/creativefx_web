<?php

namespace Modules\Auth\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // group => [actions]
        $matrix = [
            'pages'        => ['view', 'create', 'edit', 'delete', 'publish'],
            'media'        => ['view', 'upload', 'delete'],
            'translations' => ['view', 'edit'],
            'videos'       => ['view', 'manage'],
            'showroom'     => ['view', 'manage'],
            'esg'          => ['view', 'manage'],
            'careers'      => ['view', 'manage'],
            'crm'          => ['view', 'manage'],
            'users'        => ['view', 'manage'],
            'settings'     => ['view', 'manage'],
        ];

        $rows = [];
        foreach ($matrix as $group => $actions) {
            foreach ($actions as $action) {
                $rows[] = [
                    'name'       => ucfirst($group) . ' ' . $action,
                    'slug'       => $group . '.' . $action,
                    'group'      => $group,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        $this->db->table('permissions')->ignore(true)->insertBatch($rows);

        // Grant every permission to Super Admin.
        $superAdmin = $this->db->table('roles')->where('slug', 'super-admin')->get()->getRowArray();
        if ($superAdmin !== null) {
            $permIds = array_column(
                $this->db->table('permissions')->select('id')->get()->getResultArray(),
                'id'
            );
            $map = [];
            foreach ($permIds as $pid) {
                $map[] = ['permission_id' => $pid, 'role_id' => $superAdmin['id']];
            }
            if ($map !== []) {
                $this->db->table('permission_role')->ignore(true)->insertBatch($map);
            }
        }
    }
}
