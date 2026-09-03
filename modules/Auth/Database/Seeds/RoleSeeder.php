<?php

namespace Modules\Auth\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // The role model set out in the proposal's section 5.2, which is what
        // Clause 3.11 asks for: distinct privilege levels the Administrator
        // creates and delegates, rather than one shared login.
        $roles = [
            ['Administrator', 'super-admin', 'Full rights over all content, users, privileges, configuration and system settings'],
            ['Content Manager', 'content-manager', 'Approves and publishes across all sections; final editorial sign-off; cannot alter system configuration'],
            ['Divisional Editor', 'divisional-editor', 'Creates and edits content within the division’s own sections; submits for approval; cannot publish'],
            ['Translator', 'translator', 'Edits Sinhala and Tamil variants of existing records only; cannot alter the English source or the structure'],
            ['Moderator', 'moderator', 'Reviews and releases public comments and feedback; cannot edit site content'],
            ['Field Officer', 'field-officer', 'Portal access to submit applications, retrieve information and upload files; no public-site editing rights'],
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
