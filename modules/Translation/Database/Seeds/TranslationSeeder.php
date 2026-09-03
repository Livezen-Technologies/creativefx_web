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
            $fromFile = TranslationModel::flatten($data);

            foreach ($fromFile as $key => $value) {
                $model->putFromFile($locale, 'Site', $key, $value);
            }

            // A key deleted from the file leaves its row behind, and DbLanguage
            // prefers the table — so the string carries on being served by a
            // site whose code no longer mentions it. That is how four labels
            // from the previous brand survived this clone. Importing keeps the
            // values in step; this keeps the set of keys in step too.
            //
            // Only rows nobody has edited. A key somebody translated by hand and
            // that has since left the files is their work, not ours to bin.
            $model->pruneMissing($locale, 'Site', array_keys($fromFile));
        }
    }
}
