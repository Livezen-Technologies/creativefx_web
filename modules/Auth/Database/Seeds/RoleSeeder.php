<?php

namespace Modules\Auth\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        $roles = [
            ['Super Admin', 'super-admin', 'Full system access'],
            ['Content Manager', 'content-manager', 'Manage pages, sections, media and translations'],
            ['ESG Manager', 'esg-manager', 'Manage ESG metrics, reports and projects'],
            ['HR Manager', 'hr-manager', 'Manage careers, vacancies and applications'],
            ['Marketing Manager', 'marketing-manager', 'Manage CRM, leads and marketing content'],
            ['Viewer', 'viewer', 'Read-only access'],
        ];

        $rows = [];
        foreach ($roles as [$name, $slug, $desc]) {
            $rows[] = [
                'name'        => $name,
                'slug'        => $slug,
                'description' => $desc,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        $this->db->table('roles')->ignore(true)->insertBatch($rows);
    }
}
