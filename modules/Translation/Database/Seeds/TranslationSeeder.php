<?php

namespace Modules\Translation\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Modules\Translation\Models\TranslationModel;

/**
 * Seeds the translations table from the Site language files (group "Site") so
 * the Translation Manager has the UI chrome strings ready to edit. Existing
 * values are preserved (onlyIfMissing) so re-seeding never clobbers edits.
 *
 * A locale with no language file of its own is seeded from English, which puts
 * a complete, readable set of rows in front of the translator instead of a
 * screen of blanks. (CI4's lang() already falls back to English at render time,
 * so an untranslated locale reads correctly on the site meanwhile.)
 */
class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $model = model(TranslationModel::class);

        // Follows the configured locale set, so adding or dropping a language
        // is a config change rather than an edit here.
        foreach (config('App')->supportedLocales as $locale) {
            $data = $this->strings($locale) ?? $this->strings('en');
            if ($data === null) {
                continue;
            }

            foreach (TranslationModel::flatten($data) as $key => $value) {
                $model->put($locale, 'Site', $key, $value, true);
            }
        }
    }

    /** The Site language array for a locale, or null when it has no file. */
    private function strings(string $locale): ?array
    {
        $file = ROOTPATH . 'modules/Site/Language/' . $locale . '/Site.php';
        if (! is_file($file)) {
            return null;
        }

        $data = require $file;

        return is_array($data) ? $data : null;
    }
}
