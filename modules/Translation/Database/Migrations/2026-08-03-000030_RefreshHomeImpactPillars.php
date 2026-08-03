<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The home Our Impact tiles were still showing three generic sustainability
 * topics — "Net-zero ambition", "Ethical workplaces", "Circular materials" —
 * none of which are Norlanka pillars. The three Better Tomorrow pillars are
 * Protect Our Environment, Together With People and Trust In Everything, and
 * they are what the Our Impact page itself is built around.
 *
 * The language files were corrected to the real pillars some time ago, but
 * TranslationSeeder writes with onlyIfMissing and never rewrites an existing
 * row, so the correction never reached the live site. Delete the superseded
 * rows and let the seeder — which runs straight after migrations — re-import
 * them from the files.
 *
 * `body` goes too: the old standfirst described the tiles it sat above, so
 * leaving it would introduce copy that no longer matches the pillars.
 * eyebrow, title and cta are deliberately left alone — they already read
 * correctly, and preserving them keeps any wording changed in the
 * Translation Manager.
 */
class RefreshHomeImpactPillars extends Migration
{
    private const SUPERSEDED = [
        'home.impact.body',
        'home.impact.p1_t',
        'home.impact.p1_d',
        'home.impact.p2_t',
        'home.impact.p2_d',
        'home.impact.p3_t',
        'home.impact.p3_d',
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
