<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The live shop still renders the previous brand's copy — "Product catalog",
 * "Our Products", "Explore representative styles from every category we
 * manufacture" — even though the language files were rewritten for Magic Corn
 * weeks ago and the deployed build contains the new wording.
 *
 * DbLanguage serves strings from the translations table in preference to the
 * files, and TranslationSeeder writes onlyIfMissing: once a key exists it is
 * never rewritten. The rebrand migration cleared the table so the new copy
 * could be imported, but any string reworded in a LATER commit than that
 * migration has been stuck ever since, because the clear only ever runs once.
 *
 * This is the fourth time that has bitten — the showroom standfirst, the map
 * legend and the impact pillars each needed a migration of their own — so this
 * one is general: every Site row whose value no longer matches its language
 * file is deleted, and TranslationSeeder (which runs straight after migrations)
 * re-imports it. Rows that already agree are left untouched.
 *
 * The trade-off is deliberate and worth stating: a string reworded in the
 * Translation Manager and not in the file will be replaced by the file's
 * version. On this site the table holds the previous company's copy, which is
 * a worse thing to keep than an edit is to lose.
 */
class RefreshSiteStringsFromFiles extends Migration
{
    public function up(): void
    {
        if (! $this->db->tableExists('translations')) {
            return;
        }

        $stale = [];

        foreach (config('App')->supportedLocales as $locale) {
            $file = ROOTPATH . 'modules/Site/Language/' . $locale . '/Site.php';
            if (! is_file($file)) {
                continue;
            }

            $fromFile = $this->flatten((array) (require $file));

            $rows = $this->db->table('translations')
                ->select('id, `key`, value', false)
                ->where('locale', $locale)
                ->where('group', 'Site')
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $key = $row['key'];

                // A key the files no longer define is dead either way; one whose
                // wording has moved on needs re-importing.
                if (! array_key_exists($key, $fromFile) || $fromFile[$key] !== $row['value']) {
                    $stale[] = (int) $row['id'];
                }
            }
        }

        foreach (array_chunk($stale, 200) as $chunk) {
            $this->db->table('translations')->whereIn('id', $chunk)->delete();
        }

        log_message('info', 'RefreshSiteStringsFromFiles: cleared ' . count($stale) . ' stale rows');
    }

    public function down(): void
    {
        // Strings are re-imported by TranslationSeeder from the language files.
    }

    /** @return array<string, string> dotted key => string */
    private function flatten(array $node, string $prefix = ''): array
    {
        $out = [];

        foreach ($node as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $out += $this->flatten($value, $path);
            } else {
                $out[$path] = (string) $value;
            }
        }

        return $out;
    }
}
