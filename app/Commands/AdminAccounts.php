<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Auth\Database\Seeds\RoleSeeder;
use Modules\Auth\Models\UserModel;

/**
 * Who can sign into the console, and a way back in when nobody can.
 *
 * Written because an administrator was locked out and there was no way to find
 * out why. "The password does not work" has at least five causes that look
 * identical at the form — the account does not exist, it exists under another
 * address, it is suspended, it holds no staff role so `Auth::login()` refuses a
 * correct password on purpose, or the password really is wrong — and the login
 * page deliberately says the same thing for all of them, because telling a
 * stranger which of those is true is telling them whether an address is a staff
 * account.
 *
 * That is right for the form and useless for the person who owns the server, so
 * this answers it from the CLI, where being able to read the answer already
 * implies root.
 *
 *   php spark admin:accounts               who can sign in, and why or why not
 *   php spark admin:accounts reset a@b.com set a new password, printed once
 *
 * `reset` is the recovery path there was not one of: the seeder generates the
 * first password and prints it once, and after that nothing in the product
 * could set one. Note that a password printed by a run of this over SSH lands
 * in that run's log, so change it at /admin/account as soon as you are in.
 */
class AdminAccounts extends BaseCommand
{
    protected $group       = 'TSHDA';
    protected $name        = 'admin:accounts';
    protected $description = 'List the accounts that may sign into the console, or set a password.';
    protected $usage       = 'admin:accounts [list|reset] [email]';

    public function run(array $params)
    {
        $action = $params[0] ?? 'list';

        if ($action === 'list') {
            return $this->list();
        }

        if ($action !== 'reset') {
            CLI::error('Unknown action: ' . $action . '. Use list or reset.');

            return 1;
        }

        return $this->reset(trim((string) ($params[1] ?? '')));
    }

    private function list(): int
    {
        $users = new UserModel();
        $rows  = $users->orderBy('id', 'ASC')->findAll(200);

        if ($rows === []) {
            CLI::error('There are no users at all. `php spark db:seed ...DatabaseSeeder` creates the first administrator.');

            return 1;
        }

        $table = [];
        $staff = 0;

        foreach ($rows as $row) {
            $roles = $users->roleSlugs((int) $row['id']);
            $isStaff = array_intersect($roles, RoleSeeder::STAFF) !== [];
            $staff  += $isStaff ? 1 : 0;

            // Exactly the two conditions Auth::login() applies after the
            // password, named so a refusal can be explained rather than guessed.
            $why = [];
            if (($row['status'] ?? 'active') !== 'active') {
                $why[] = 'status is ' . ($row['status'] ?? '?');
            }
            if (! $isStaff) {
                $why[] = 'no staff role';
            }
            if (trim((string) ($row['password_hash'] ?? '')) === '') {
                $why[] = 'no password set';
            }

            $table[] = [
                (string) $row['id'],
                (string) $row['email'],
                $isStaff ? 'yes' : 'no',
                implode(', ', $roles) ?: '—',
                $why === [] ? 'can sign in' : implode('; ', $why),
                (string) ($row['last_login_at'] ?? '—'),
            ];
        }

        CLI::table($table, ['id', 'email', 'staff', 'roles', 'console access', 'last sign-in']);
        CLI::newLine();

        if ($staff === 0) {
            CLI::error('No account holds a staff role, so nobody can sign into the console.');
            CLI::write('Give one a role with:  php spark admin:accounts reset <email>', 'dark_gray');

            return 1;
        }

        CLI::write($staff . ' account(s) can reach the console.', 'green');
        CLI::write('A correct password is still refused for anything not marked "can sign in".', 'dark_gray');

        return 0;
    }

    private function reset(string $email): int
    {
        if ($email === '') {
            CLI::error('Which account? php spark admin:accounts reset <email>');

            return 1;
        }

        $users = new UserModel();
        $user  = $users->findByEmail($email);

        if ($user === null) {
            CLI::error('No account with that address: ' . $email);
            CLI::write('Run `php spark admin:accounts` to see the addresses that exist.', 'dark_gray');

            return 1;
        }

        $password = bin2hex(random_bytes(9));

        $users->update($user['id'], [
            'password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            // A locked-out administrator is usually locked out by more than the
            // password, and fixing one of three things is not fixing it.
            'status'        => 'active',
        ]);

        $roleId = db_connect()->table('roles')->where('slug', 'super-admin')->get()->getRowArray()['id'] ?? null;
        if ($roleId !== null) {
            db_connect()->table('role_user')->ignore(true)->insert([
                'role_id' => $roleId,
                'user_id' => (int) $user['id'],
            ]);
        }

        CLI::newLine();
        CLI::write('  Password set for ' . $email, 'green');
        CLI::write('    password: ' . $password);
        CLI::newLine();
        CLI::write('  This is now in the output of whatever ran it — a terminal you trust, or', 'yellow');
        CLI::write('  a build log anyone with the repository can read. Sign in and change it', 'yellow');
        CLI::write('  at /admin/account straight away.', 'yellow');
        CLI::newLine();

        return 0;
    }
}
