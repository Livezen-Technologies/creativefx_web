<?php

namespace Modules\Auth\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now   = date('Y-m-d H:i:s');
        $email = 'admin@norlanka.local';

        $existing = $this->db->table('users')->where('email', $email)->get()->getRowArray();
        if ($existing === null) {
            $this->db->table('users')->insert([
                'email'         => $email,
                'username'      => 'admin',
                // Dev credentials — CHANGE in production. Password: "norlanka123"
                'password_hash' => password_hash('norlanka123', PASSWORD_DEFAULT),
                'first_name'    => 'Norlanka',
                'last_name'     => 'Admin',
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);
        }

        $userId = (int) $this->db->table('users')->where('email', $email)->get()->getRowArray()['id'];
        $roleId = $this->db->table('roles')->where('slug', 'super-admin')->get()->getRowArray()['id'] ?? null;

        if ($roleId !== null) {
            $this->db->table('role_user')->ignore(true)->insert([
                'role_id' => $roleId,
                'user_id' => $userId,
            ]);
        }
    }
}
