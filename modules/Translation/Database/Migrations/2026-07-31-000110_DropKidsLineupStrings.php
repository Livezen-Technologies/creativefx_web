<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The kids-lineup hero on the showroom index used placeholder figure artwork
 * and has been removed, so its copy is dead. It was seeded into the
 * translations table, which TranslationSeeder never rewrites (onlyIfMissing),
 * so clear it out to keep the Translation Manager free of orphaned entries.
 */
class DropKidsLineupStrings extends Migration
{
    public function up(): void
    {
        $this->db->table('translations')
            ->where('group', 'Site')
            ->like('key', 'showroom.kids_', 'after')
            ->delete();
    }

    public function down(): void
    {
        // Strings are re-imported by TranslationSeeder if ever reinstated.
    }
}
