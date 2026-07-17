<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One-time refresh of UI strings amended by the final website content sheet
 * (July 2026): the home "who we are" intro (25+ manufacturing partners) and
 * the India footprint role (Norlanka Manufacturing India — Bangalore).
 *
 * A migration (not the seeder) so it runs exactly once per environment and
 * future Translation Manager edits are never clobbered by redeploys.
 */
class RefreshContentSheetStrings extends Migration
{
    private const KEYS = ['home.intro.body', 'home.footprint.roles.india'];

    public function up(): void
    {
        foreach (['en', 'ja', 'es', 'zh'] as $locale) {
            $file = ROOTPATH . 'modules/Site/Language/' . $locale . '/Site.php';
            if (! is_file($file)) {
                continue;
            }
            $data = require $file;
            if (! is_array($data)) {
                continue;
            }
            $flat = \Modules\Translation\Models\TranslationModel::flatten($data);

            foreach (self::KEYS as $key) {
                if (! isset($flat[$key])) {
                    continue;
                }
                $this->db->table('translations')
                    ->where(['locale' => $locale, 'group' => 'Site', 'key' => $key])
                    ->update(['value' => $flat[$key]]);
            }
        }
    }

    public function down(): void
    {
        // Content refresh — nothing to restore.
    }
}
