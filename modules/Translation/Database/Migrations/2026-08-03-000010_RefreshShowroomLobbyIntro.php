<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The showroom lobby standfirst still invited visitors to "open any piece for
 * fabric, MOQ, sizes and inquiries" — all of which were stripped out when the
 * product drawer was cut back to photography and description. It now reads as
 * an invitation to browse the collections instead.
 *
 * TranslationSeeder writes with onlyIfMissing and never rewrites an existing
 * row, so editing the language files alone would leave the live site showing
 * the old promise. Delete the superseded row and let the seeder — which runs
 * straight after migrations — re-import it from the corrected files.
 */
class RefreshShowroomLobbyIntro extends Migration
{
    public function up(): void
    {
        $this->db->table('translations')
            ->where('group', 'Site')
            ->where('key', 'showroom.lobby_intro')
            ->delete();
    }

    public function down(): void
    {
        // Strings are re-imported by TranslationSeeder from the language files.
    }
}
