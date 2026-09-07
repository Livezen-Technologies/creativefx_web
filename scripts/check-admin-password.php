<?php

/**
 * Can an administrator change their own password?
 *
 * Until this screen existed the answer was no — not in the console and not on
 * the command line — while the seeder printed the first administrator's
 * password into the deploy log and told them to go and change it. So the
 * assertions here are about the whole round trip rather than the form: sign in,
 * change it, and prove the old password stops working and the new one starts.
 *
 * Start the server first:
 *   PHP_CLI_SERVER_WORKERS=6 php spark serve --port 8083
 *   php scripts/check-admin-password.php
 *
 * It creates a staff account and deletes it afterwards, so point it at a
 * development database.
 */

define('FCPATH', dirname(__DIR__) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
define('ENVIRONMENT', 'development');
CodeIgniter\Boot::bootConsole($paths);

use Modules\Auth\Database\Seeds\RoleSeeder;

$base = getenv('BASE_URL') ?: 'http://127.0.0.1:8083';
$db   = db_connect();
$now  = date('Y-m-d H:i:s');

$email = 'password-check@example.invalid';
$old   = 'the original passphrase';
$new   = 'a completely different passphrase';

$fail  = 0;
$check = static function (string $what, $got, $want) use (&$fail): void {
    $ok = $got === $want;
    printf("  %-54s %s (got %s, want %s)\n", $what, $ok ? 'ok' : 'FAIL', var_export($got, true), var_export($want, true));
    $fail += $ok ? 0 : 1;
};

$db->table('users')->where('email', $email)->delete();
$db->table('users')->insert([
    'email' => $email, 'password_hash' => password_hash($old, PASSWORD_DEFAULT),
    'first_name' => 'Password', 'last_name' => 'Check', 'status' => 'active',
    'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now,
]);
$userId = (int) $db->insertID();

// Staff, or the sign-in refuses it — see Auth::login().
$roleId = (int) ($db->table('roles')->select('id')->whereIn('slug', RoleSeeder::STAFF)->get(1)->getRowArray()['id'] ?? 0);
if ($roleId === 0) {
    fwrite(STDERR, "no staff role in the database\n");
    exit(2);
}
$db->table('role_user')->insert(['user_id' => $userId, 'role_id' => $roleId]);

$jar = tempnam(sys_get_temp_dir(), 'adminpw');

$curl = static function (string $path, array $post = [], bool $follow = true) use ($base, $jar): array {
    $cmd = ['curl', '-s', '-c', $jar, '-b', $jar, '-w', '\n%{http_code}\n%{url_effective}'];
    if ($follow) { $cmd[] = '-L'; }
    foreach ($post as $k => $v) { $cmd[] = '-d'; $cmd[] = $k . '=' . $v; }
    $cmd[] = $base . $path;
    $out   = shell_exec(implode(' ', array_map('escapeshellarg', $cmd)) . ' 2>/dev/null');
    $parts = explode("\n", (string) $out);
    $url   = array_pop($parts);
    $code  = array_pop($parts);

    return ['body' => implode("\n", $parts), 'code' => (int) $code, 'url' => $url];
};

/** The CSRF field the framework put on the page we are about to post from. */
$token = static function (string $html): array {
    preg_match('/name="([a-z0-9_]*(?:csrf|token)[a-z0-9_]*)"\s+value="([^"]+)"/i', $html, $m);

    return $m ? [$m[1] => $m[2]] : [];
};

$signIn = static function (string $password) use ($curl, $token, $email, $jar): bool {
    @unlink($jar);
    $form = $curl('/admin/login');
    $res  = $curl('/admin/login', $token($form['body']) + ['email' => $email, 'password' => $password]);

    // The console renders the sidebar; the login page renders the form again.
    return ! str_contains($res['body'], 'name="password"');
};

try {
    $check('the old password signs in', $signIn($old), true);

    $page = $curl('/admin/account');
    $check('the account screen is reachable', $page['code'], 200);
    $check('and carries a change-password form',
        str_contains($page['body'], 'name="current_password"'), true);

    // Each refusal on its own, from a fresh page so the CSRF token is current.
    $post = static function (array $fields) use ($curl, $token) {
        $form = $curl('/admin/account');

        return $curl('/admin/account/password', $token($form['body']) + $fields);
    };

    $r = $post(['current_password' => 'not+the+password', 'new_password' => $new, 'confirm_password' => $new]);
    $check('a wrong current password is refused',
        str_contains($r['body'], 'current password is not right'), true);

    $r = $post(['current_password' => $old, 'new_password' => $new, 'confirm_password' => $new . 'x']);
    $check('a mismatched confirmation is refused',
        str_contains($r['body'], 'do not match'), true);

    $r = $post(['current_password' => $old, 'new_password' => 'short', 'confirm_password' => 'short']);
    $check('a short password is refused',
        str_contains($r['body'], 'at least 12 characters'), true);

    $r = $post(['current_password' => $old, 'new_password' => $old, 'confirm_password' => $old]);
    $check('re-setting the same password is refused',
        str_contains($r['body'], 'same as the current one'), true);

    $check('and none of that changed the password',
        password_verify($old, (string) $db->table('users')->where('id', $userId)->get()->getRowArray()['password_hash']), true);

    // The one that must work.
    $r = $post(['current_password' => $old, 'new_password' => $new, 'confirm_password' => $new]);
    $check('a valid change is accepted',
        str_contains($r['body'], 'password has been changed'), true);

    $row = $db->table('users')->where('id', $userId)->get()->getRowArray();
    $check('the stored hash is the new password',
        password_verify($new, (string) $row['password_hash']), true);
    $check('and no longer the old one',
        password_verify($old, (string) $row['password_hash']), false);

    // The round trip, which is the whole point.
    $check('the old password no longer signs in', $signIn($old), false);
    $check('the new password signs in', $signIn($new), true);
} finally {
    @unlink($jar);
    $db->table('role_user')->where('user_id', $userId)->delete();
    $db->table('users')->where('id', $userId)->delete();
}

echo "\n", $fail === 0 ? "all checks passed\n" : "$fail check(s) FAILED\n";
exit($fail === 0 ? 0 : 1);
