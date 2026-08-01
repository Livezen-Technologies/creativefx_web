<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The home "What we do" grid was re-scoped from four production capabilities
 * to four service pillars, and its heading now reads "solutions" rather than
 * "manufacturing".
 *
 * TranslationSeeder writes with onlyIfMissing and never rewrites an existing
 * row, so editing the language files alone would leave the live site showing
 * the old copy. Delete the superseded rows and let the seeder — which runs
 * straight after migrations — re-import them from the corrected files. The
 * new mfg_/sourcing_/partner_ keys have no rows yet and simply get inserted;
 * design_t/design_d are reused under new wording, so they must go too.
 *
 * eyebrow and intro are deliberately left alone: they still read correctly,
 * and preserving them keeps any wording changed in the Translation Manager.
 */
class RefreshCapabilityStrings extends Migration
{
    private const SUPERSEDED = [
        'home.cap.title',
        'home.cap.apparel_t',
        'home.cap.apparel_d',
        'home.cap.washing_t',
        'home.cap.washing_d',
        'home.cap.printing_t',
        'home.cap.printing_d',
        'home.cap.design_t',
        'home.cap.design_d',
    ];

    public function up(): void
    {
        $this->db->table('translations')
            ->where('group', 'Site')
            ->whereIn('key', self::SUPERSEDED)
            ->delete();
    }

    public function down(): void
    {
        // Strings are re-imported by TranslationSeeder from the language files.
    }
}
