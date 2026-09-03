<?php

namespace Modules\Core\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Take a stranger's email address off the hotel's public site.
 *
 * The footer and the contact page have both been publishing
 * shankerv@viswakula.com — a real address belonging to somebody at an unrelated
 * company, left over from whatever this template was first built for.
 * RebrandToMagicCorn wrote it into `settings`, SettingSeeder inserts with
 * `ignore` and so never revisited the row, and an earlier fix of mine changed
 * only the migration's source — which helps a fresh install and does nothing
 * for the database that already has the value.
 *
 * Cleared rather than replaced, because the hotel's own address is not known:
 * giantforests.com obfuscates both of theirs. Every place this renders is
 * guarded on a non-empty value, so an empty setting shows no email row at all
 * — which is the right state until the real address is supplied, and a great
 * deal better than continuing to publish someone else's inbox.
 *
 * Conditional on that exact value, so anything since entered in the admin
 * survives.
 */
class ClearForeignContactEmail extends Migration
{
    private const FOREIGN = 'shankerv@viswakula.com';

    public function up(): void
    {
        if (! $this->db->tableExists('settings')) {
            return;
        }

        $this->db->table('settings')
            ->where('group', 'contact')->where('key', 'email')
            ->where('value', self::FOREIGN)
            ->update(['value' => '', 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function down(): void
    {
        // Deliberately not reversible: putting a third party's address back on a
        // public site is not something a rollback should do.
    }
}
