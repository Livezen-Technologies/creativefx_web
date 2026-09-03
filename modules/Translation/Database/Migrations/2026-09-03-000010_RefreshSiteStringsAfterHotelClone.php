<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The live hotel site is still serving the corn company's copy.
 *
 * Its hero buttons read "See our flavours" and "Find an outlet", which is not a
 * stale build — the deployed templates are correct — but a stale database. The
 * sequence that produced it:
 *
 *   deploy #118 stood this site up while Site.php still held Magic Corn's
 *   wording, so TranslationSeeder imported that wording into `translations`;
 *   RefreshSiteStringsFromFiles ran in the same pass and agreed with the file,
 *   because at that moment they matched. Deploy #119 rewrote the file for the
 *   hotel — but TranslationSeeder writes onlyIfMissing, and a migration runs
 *   once, so nothing has revisited those rows since. DbLanguage prefers the
 *   table over the files, and the table has been the wrong brand ever since.
 *
 * This runs the same sweep again now that the file is right: every Site row
 * whose value no longer matches its language file is deleted, and
 * TranslationSeeder re-imports it immediately afterwards. Rows that already
 * agree are untouched.
 *
 * The trade-off is the one that migration already stated, and it still holds:
 * a string reworded in the Translation Manager and not in the file will be
 * replaced by the file's. This table currently holds another company's copy,
 * which is worse to keep than an edit is to lose — and this site is hours old,
 * so there are no such edits to lose.
 *
 * Two earlier migrations tried to correct individual keys and matched nothing,
 * because they guarded on the hotel wording those rows never contained. The
 * lesson is to sync from the file rather than guess which wrong value is there.
 */
class RefreshSiteStringsAfterHotelClone extends Migration
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

                if (! array_key_exists($key, $fromFile) || $fromFile[$key] !== $row['value']) {
                    $stale[] = (int) $row['id'];
                }
            }
        }

        foreach (array_chunk($stale, 200) as $chunk) {
            $this->db->table('translations')->whereIn('id', $chunk)->delete();
        }

        log_message('info', 'RefreshSiteStringsAfterHotelClone: cleared ' . count($stale) . ' stale rows');
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
