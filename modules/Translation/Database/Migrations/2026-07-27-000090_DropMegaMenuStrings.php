<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The "Our Expertise" mega-menu was removed from the header, so its strings
 * are dead. They were seeded into the translations table, so clear them out
 * to keep the Translation Manager free of orphaned entries.
 */
class DropMegaMenuStrings extends Migration
{
    public function up(): void
    {
        $this->db->table('translations')
            ->where('group', 'Site')
            ->like('key', 'mega.', 'after')
            ->delete();
    }

    public function down(): void
    {
        // Strings are re-imported by TranslationSeeder if ever reinstated.
    }
}
