<?php

namespace Modules\Account\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The `users` table was written for staff: an email, a password and a role, all
 * created by an administrator who already knows who you are. A learner arrives
 * the other way round — they register themselves, mid-purchase, from a country
 * we have not asked about, and everything the school later sends them depends
 * on facts nobody has collected yet.
 *
 * So: the columns a self-registering account needs, and the three token
 * columns that make registration, verification and password reset possible
 * without a second table.
 *
 * The tokens are stored as hashes, not as the value that goes in the email.
 * A verification link sitting in plain text in the database is a password reset
 * for every account, available to anybody who can read a backup — and the
 * column has to be a hash from the first migration, because migrating one later
 * means silently invalidating everybody's in-flight link.
 *
 * `country`, `timezone` and `locale` are on the user rather than only on the
 * order because they decide things outside a purchase: which price book they
 * see next time, what time a reminder says the class starts, and which language
 * their certificate is issued in.
 */
class ExtendUsersForLearners extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'phone'             => ['type' => 'VARCHAR', 'constraint' => 48, 'null' => true, 'after' => 'last_name'],
            'country'           => ['type' => 'CHAR', 'constraint' => 2, 'null' => true],
            // Stored per user so a reminder can say "09:00 in Colombo, which is
            // 05:30 where you are" rather than making the learner do it.
            'timezone'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'locale'            => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
            'company'           => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'job_title'         => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true],
            // Opt-in, recorded with the moment it was given. The Sri Lanka PDPA
            // and the GDPR both ask you to be able to show *when* consent was
            // obtained, which a boolean alone cannot answer.
            'marketing_opt_in'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'consent_at'        => ['type' => 'DATETIME', 'null' => true],
            'email_verified_at' => ['type' => 'DATETIME', 'null' => true],
            'verify_token'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'verify_expires_at' => ['type' => 'DATETIME', 'null' => true],
            'reset_token'       => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'reset_expires_at'  => ['type' => 'DATETIME', 'null' => true],
            'remember_token'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'avatar'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);

        // Looked up by token on every click of a verification or reset link, so
        // indexed. Not unique: two hashes colliding is not a thing that happens,
        // but a unique index would also forbid two rows with the token cleared
        // on some engines, and the null-handling difference between MySQL and
        // SQLite is not worth relying on.
        //
        // `CREATE INDEX IF NOT EXISTS` is valid SQLite — which .env uses locally
        // — and a syntax error on MySQL 8, which is what production runs. A
        // migration that passes every local test and fails on the deploy is
        // exactly the kind of thing that is discovered at the worst moment, so
        // existence is checked through the driver rather than asserted in SQL.
        $this->addIndexOnce('users_verify_token', 'verify_token');
        $this->addIndexOnce('users_reset_token', 'reset_token');
    }

    public function down(): void
    {
        foreach (['users_verify_token', 'users_reset_token'] as $index) {
            // Dropping an index is spelled differently on the two engines too:
            // SQLite's index names are database-wide, MySQL's are per table.
            if (! $this->hasIndex($index)) {
                continue;
            }
            $this->db->query(
                stripos($this->db->DBDriver, 'sqlite') !== false
                    ? 'DROP INDEX ' . $this->db->escapeIdentifiers($index)
                    : 'ALTER TABLE users DROP INDEX ' . $this->db->escapeIdentifiers($index)
            );
        }

        $this->forge->dropColumn('users', [
            'phone', 'country', 'timezone', 'locale', 'company', 'job_title',
            'marketing_opt_in', 'consent_at', 'email_verified_at',
            'verify_token', 'verify_expires_at', 'reset_token',
            'reset_expires_at', 'remember_token', 'avatar',
        ]);
    }

    /** Create an index on `users`, unless one of that name already exists. */
    private function addIndexOnce(string $name, string $column): void
    {
        if ($this->hasIndex($name)) {
            return;
        }

        // Portable on both drivers, unlike the IF NOT EXISTS form.
        $this->db->query(sprintf(
            'CREATE INDEX %s ON users (%s)',
            $this->db->escapeIdentifiers($name),
            $this->db->escapeIdentifiers($column)
        ));
    }

    private function hasIndex(string $name): bool
    {
        // getIndexData() is the framework's own driver-neutral read of an
        // engine's index catalogue, so this needs no SHOW INDEX / PRAGMA split.
        foreach ($this->db->getIndexData('users') as $index) {
            if (strcasecmp((string) $index->name, $name) === 0) {
                return true;
            }
        }

        return false;
    }
}
