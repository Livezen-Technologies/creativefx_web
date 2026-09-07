<?php

namespace Modules\Learning\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One certificate per enrolment, enforced by the database.
 *
 * `CertificateService::eligible()` returns `already` when a certificate exists,
 * and that was the only thing standing between an enrolment and two
 * certificates. It is a read followed by a write with no lock between them, so
 * two administrators pressing Issue at the same moment both read "none yet" and
 * both insert. Nothing about that is unlikely: the enrolments queue is a list
 * with a button on every row, and a class finishing is exactly when two people
 * work through it together.
 *
 * A duplicate is worse than an extra row. Two certificates for one seat means
 * two serials and two verification codes for the same claim, and the enrolments
 * list joins certificates — so a duplicate fans that join out and quietly breaks
 * the pagination, whose total is counted separately.
 *
 * `enrolment_id` is nullable and stays nullable: a certificate can be issued
 * without an enrolment behind it. Both MySQL and SQLite allow any number of
 * NULLs in a unique index, which is exactly the rule wanted here — unique per
 * enrolment where there is one, unconstrained where there is not.
 */
class UniqueCertificatePerEnrolment extends Migration
{
    private const INDEX = 'certificates_enrolment_unique';

    public function up(): void
    {
        // A duplicate already in the table would make the index creation fail,
        // and failing a deploy is the wrong way to report it: the migration
        // clears the surplus first, keeping the earliest — the one whose serial
        // and verification code a learner may already have been given.
        $this->dropDuplicates();

        if ($this->hasIndex()) {
            return;
        }

        // Written out rather than through Forge: addUniqueKey() only applies at
        // createTable(), and `CREATE UNIQUE INDEX IF NOT EXISTS` is valid SQLite
        // and a syntax error on MySQL 8 — which is what production runs.
        $this->db->query(sprintf(
            'CREATE UNIQUE INDEX %s ON certificates (%s)',
            $this->db->escapeIdentifiers(self::INDEX),
            $this->db->escapeIdentifiers('enrolment_id')
        ));
    }

    public function down(): void
    {
        if (! $this->hasIndex()) {
            return;
        }

        $this->db->query(
            stripos($this->db->DBDriver, 'sqlite') !== false
                ? 'DROP INDEX ' . $this->db->escapeIdentifiers(self::INDEX)
                : 'ALTER TABLE certificates DROP INDEX ' . $this->db->escapeIdentifiers(self::INDEX)
        );
    }

    /** Keep the earliest certificate for each enrolment, delete the rest. */
    private function dropDuplicates(): void
    {
        $rows = $this->db->query(
            'SELECT enrolment_id, MIN(id) AS keep_id, COUNT(*) AS n
               FROM certificates
              WHERE enrolment_id IS NOT NULL
           GROUP BY enrolment_id
             HAVING COUNT(*) > 1'
        )->getResultArray();

        foreach ($rows as $row) {
            $this->db->table('certificates')
                ->where('enrolment_id', (int) $row['enrolment_id'])
                ->where('id !=', (int) $row['keep_id'])
                ->delete();

            log_message('warning', 'Removed {n} duplicate certificate(s) for enrolment {id}.', [
                'n'  => (int) $row['n'] - 1,
                'id' => (int) $row['enrolment_id'],
            ]);
        }
    }

    private function hasIndex(): bool
    {
        foreach ($this->db->getIndexData('certificates') as $index) {
            if (strcasecmp((string) $index->name, self::INDEX) === 0) {
                return true;
            }
        }

        return false;
    }
}
