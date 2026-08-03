<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The home footprint map lost its dot legend — the region list underneath
 * already names every location, and the HQ dot reads as the primary marker
 * without being labelled.
 *
 * The two strings were seeded into the translations table, which
 * TranslationSeeder never rewrites (onlyIfMissing), so clear them out rather
 * than leave orphans in the Translation Manager.
 */
class DropFootprintLegendStrings extends Migration
{
    private const DEAD = [
        'home.footprint.legend_hq',
        'home.footprint.legend_market',
    ];

    public function up(): void
    {
        $this->db->table('translations')
            ->where('group', 'Site')
            ->whereIn('key', self::DEAD)
            ->delete();
    }

    public function down(): void
    {
        // Strings are re-imported by TranslationSeeder from the language files.
    }
}
