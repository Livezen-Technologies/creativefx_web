<?php

namespace Modules\Translation\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The site shipped with the previous brand's languages — English, Japanese,
 * Spanish and Chinese. For a Sri Lankan food brand the set is English, Sinhala
 * and Tamil, and the config now says so.
 *
 * Config alone is not enough. Translations already imported into the database
 * are never rewritten by TranslationSeeder (it writes onlyIfMissing), and the
 * seeded page and product content carries its own {locale: text} maps. Both
 * would keep serving Japanese and Spanish to a Translation Manager that no
 * longer lists those languages.
 *
 * So: drop the rows and the keys for every locale the application no longer
 * supports, reading the supported set from config rather than a literal, and
 * take the previous brand's name off the home page while we are in that column.
 */
class SwitchToSinhalaAndTamil extends Migration
{
    /** Never rewritten: framework bookkeeping and the translation store itself. */
    private const SKIP = ['migrations', 'translations'];

    public function up(): void
    {
        $supported = config('App')->supportedLocales;

        // 1. Translation rows for languages that are gone.
        if ($this->db->tableExists('translations')) {
            $this->db->table('translations')->whereNotIn('locale', $supported)->delete();
        }

        // 2. Locale keys inside seeded content. Every table is scanned rather
        //    than a named list: the first draft of this listed eleven tables
        //    and still missed esg_metrics, which is precisely the drift that
        //    left four languages configured on a Sri Lankan site. Only values
        //    that parse as JSON and actually change are written back.
        foreach ($this->db->listTables() as $table) {
            if (in_array($table, self::SKIP, true)) {
                continue;
            }

            $fields = $this->db->getFieldNames($table);
            if (! in_array('id', $fields, true)) {
                continue;
            }

            foreach ($this->db->table($table)->get()->getResultArray() as $row) {
                $update = [];

                foreach ($row as $column => $raw) {
                    if (! is_string($raw) || $raw === '' || ($raw[0] !== '{' && $raw[0] !== '[')) {
                        continue;
                    }

                    $decoded = json_decode($raw, true);
                    if (! is_array($decoded)) {
                        continue;
                    }

                    $json = json_encode($this->prune($decoded, $supported), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    if ($json !== false && $json !== $raw) {
                        $update[$column] = $json;
                    }
                }

                if ($update !== []) {
                    $this->db->table($table)->where('id', $row['id'])->update($update);
                }
            }
        }

        // 3. The home page title was never rebranded.
        if ($this->db->tableExists('pages')) {
            $row = $this->db->table('pages')->select('id, title')->where('slug', 'home')->get()->getRowArray();
            if ($row !== null && str_contains((string) $row['title'], 'Norlanka')) {
                $this->db->table('pages')->where('id', $row['id'])->update([
                    'title'      => json_encode(['en' => 'Magic Corn'], JSON_UNESCAPED_UNICODE),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Dropped languages are re-imported by TranslationSeeder from whatever
        // language files exist; the content they were pruned from is reseeded.
    }

    /**
     * Remove unsupported languages from every {locale: text} map in a tree.
     *
     * A node counts as a locale map when it has an English side and every key
     * is a language tag — matching the shape, not a fixed list, so a locale the
     * site has already forgotten about is still recognised and cleaned.
     */
    private function prune(array $node, array $supported): array
    {
        if ($this->isLocaleMap($node)) {
            return array_intersect_key($node, array_flip($supported));
        }

        foreach ($node as $key => $value) {
            if (is_array($value)) {
                $node[$key] = $this->prune($value, $supported);
            }
        }

        return $node;
    }

    private function isLocaleMap(array $node): bool
    {
        if (! isset($node['en']) || ! is_string($node['en'])) {
            return false;
        }

        foreach (array_keys($node) as $key) {
            if (! is_string($key) || preg_match('/^[a-z]{2}(-[A-Za-z]{2,4})?$/', $key) !== 1) {
                return false;
            }
        }

        return true;
    }
}
