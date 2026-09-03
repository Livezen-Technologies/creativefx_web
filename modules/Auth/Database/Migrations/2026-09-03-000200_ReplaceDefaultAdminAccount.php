<?php

namespace Modules\Auth\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Retire the shipped admin account.
 *
 * The site was standing up with admin@norlanka.local and the password
 * "norlanka123" — the previous brand's name, a password in the seeder's own
 * comment, and until this release both of them pre-filled into the login form
 * with a line underneath naming them as the credentials. Anyone who found
 * /admin/login had the account.
 *
 * This renames that user to the hotel's own address and sets a new password.
 * The hash is committed rather than the password: bcrypt at cost 12 over a
 * three-word passphrase with a four-digit suffix is not something a copy of
 * this repository gets you into, and the alternative — a password typed into a
 * server by hand — is one nobody would be able to find again.
 *
 * The account is matched by id and by either address, so this is safe to run on
 * a database where somebody has already renamed the user by hand, and safe to
 * run twice.
 */
class ReplaceDefaultAdminAccount extends Migration
{
    private const OLD_EMAIL = 'admin@norlanka.local';
    private const NEW_EMAIL = 'admin@giantforests.com';
    private const NEW_HASH  = '$2y$12$uIvmYZuK7OYpeyBpr3xYPus.MwjINVnjjPZGCj1L14oAcKsXED8AG';

    public function up(): void
    {
        $users = $this->db->table('users');
        $now   = date('Y-m-d H:i:s');

        $user = $users->where('email', self::OLD_EMAIL)->get()->getRowArray()
            ?? $users->where('email', self::NEW_EMAIL)->get()->getRowArray();

        if ($user === null) {
            // Nothing to rename — a site that has already been given its own
            // administrator should not have a second one created behind it.
            return;
        }

        $users->where('id', $user['id'])->update([
            'email'         => self::NEW_EMAIL,
            'password_hash' => self::NEW_HASH,
            'first_name'    => 'Giants Forest',
            'last_name'     => 'Admin',
            'status'        => 'active',
            'updated_at'    => $now,
        ]);

        // If the rename left a duplicate behind — the old row still present
        // alongside a hand-made new one — the old one goes.
        $users->where('email', self::OLD_EMAIL)->where('id !=', $user['id'])->delete();
    }

    public function down(): void
    {
        // Deliberately not reversible. Rolling this back would restore a
        // published password on a live site, which is not a state worth being
        // able to return to.
    }
}
