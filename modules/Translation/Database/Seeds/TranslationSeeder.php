<?php

namespace Modules\Translation\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Modules\Translation\Models\TranslationModel;

/**
 * Seeds the translations table from the Site language files (group "Site") so
 * the Translation Manager has the UI chrome strings ready to edit. Existing
 * values are preserved (onlyIfMissing) so re-seeding never clobbers edits.
 */
class TranslationSeeder extends Seeder
{
    public function run(): void
    {
        $model = model(TranslationModel::class);

        foreach (config('App')->supportedLocales as $locale) {
            $file = ROOTPATH . 'modules/Site/Language/' . $locale . '/Site.php';
            if (! is_file($file)) {
                continue;
            }
            $data = require $file;
            if (! is_array($data)) {
                continue;
            }
            foreach (TranslationModel::flatten($data) as $key => $value) {
                $model->put($locale, 'Site', $key, $value, true);
            }
        }
    }
}
