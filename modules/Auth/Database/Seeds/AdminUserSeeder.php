<?php

namespace Modules\Auth\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Makes sure there is exactly one way into the admin, and never a known one.
 *
 * This used to insert admin@norlanka.local with the password "norlanka123",
 * written in a comment right above it. On a site anybody can reach that is not
 * a development convenience — it is the administrator account, published, and
 * re-published on every deploy in case anyone had changed it.
 *
 * Now: an existing administrator is left alone and only has their role
 * confirmed. An account is created only when the users table is empty, which is
 * a genuinely new install, and it is given a random password that exists
 * nowhere but the terminal output of that one run. Whoever ran it can read it;
 * a copy of this repository cannot.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $now   = date('Y-m-d H:i:s');
        $users = $this->db->table('users');

        $existing = $users->where('deleted_at', null)->get()->getFirstRow('array');

        if ($existing === null) {
            $email    = 'admin@' . (parse_url((string) config('App')->baseURL, PHP_URL_HOST) ?: 'localhost');
            $password = bin2hex(random_bytes(9));

            $users->insert([
                'email'         => $email,
                'username'      => 'admin',
                'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                'first_name'    => 'Site',
                'last_name'     => 'Admin',
                'status'        => 'active',
                'created_at'    => $now,
                'updated_at'    => $now,
            ]);

            // The only time this password is ever legible. It is not written to
            // a log file, because a log file is a place passwords are found.
            if (is_cli()) {
                fwrite(STDERR, PHP_EOL
                    . "  Created the first administrator." . PHP_EOL
                    . "    email:    {$email}" . PHP_EOL
                    . "    password: {$password}" . PHP_EOL
                    . "  Sign in and change it. This is the only time it is shown." . PHP_EOL . PHP_EOL);
            }

            $existing = $users->where('email', $email)->get()->getRowArray();
        }

        $roleId = $this->db->table('roles')->where('slug', 'super-admin')->get()->getRowArray()['id'] ?? null;

        if ($roleId !== null) {
            $this->db->table('role_user')->ignore(true)->insert([
                'role_id' => $roleId,
                'user_id' => (int) $existing['id'],
            ]);
        }
    }
}
