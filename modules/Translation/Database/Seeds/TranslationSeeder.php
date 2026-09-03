<?php

namespace Modules\Translation\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Modules\Translation\Models\TranslationModel;

/**
 * Seeds the translations table from the Site language files (group "Site") so
 * the Translation Manager has the UI chrome strings ready to edit.
 *
 * Rows nobody has edited follow the files, so rewording a string in a file
 * reaches every site on its next deploy. Rows edited in the Translation Manager
 * are marked and left alone. Before that distinction existed the seeder could
 * only insert, and a file edit never reached a seeded site at all.
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
                $model->putFromFile($locale, 'Site', $key, $value);
            }
        }
    }
}
