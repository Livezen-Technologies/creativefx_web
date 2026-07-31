<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The USA and Hong Kong markers were dropped from the home-page footprint map,
 * leaving their strings orphaned in the translations table.
 *
 * TranslationSeeder writes with onlyIfMissing, so it never rewrites a row that
 * already exists — editing the language files alone would leave the live site
 * showing the old copy. Delete the affected rows instead and let the seeder,
 * which runs straight after migrations, re-import them from the corrected
 * files. `regions` is re-indexed by the removal, so its tail goes too.
 *
 * Deliberately surgical: eyebrow/title/legend rows are left alone so any
 * wording changed in the Translation Manager survives.
 */
class DropUsaHongKongFootprint extends Migration
{
    private const DEAD = [
        'home.footprint.points.america',
        'home.footprint.points.hongkong',
        'home.footprint.roles.america',
        'home.footprint.roles.hongkong',
        'home.footprint.regions.3',
        'home.footprint.regions.4',
        'home.footprint.regions.5',
    ];

    /** Still in use, but the seeded text names the two removed countries. */
    private const RESEED = [
        'home.footprint.body',
        'home.footprint.note',
    ];

    public function up(): void
    {
        $this->db->table('translations')
            ->where('group', 'Site')
            ->whereIn('key', array_merge(self::DEAD, self::RESEED))
            ->delete();
    }

    public function down(): void
    {
        // Strings are re-imported by TranslationSeeder if ever reinstated.
    }
}
