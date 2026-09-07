<?php

namespace Modules\Core\Libraries;

/**
 * Fills missing ja/es/zh values across all localized content, using the
 * dictionary in Config/ContentTranslations.php.
 *
 * Runs in two places so both existing and fresh installs end up complete:
 *   - the BackfillContentTranslations migration (databases already seeded)
 *   - the end of DatabaseSeeder (fresh installs, after every seeder has run)
 *
 * Idempotent: only empty locales are written, existing values are never
 * overwritten, so translations edited in the admin survive re-runs.
 */
class ContentTranslator
{
    /** Localized JSON columns, by table. */
    private const COLUMNS = [
        'pages'               => ['title', 'meta_description', 'meta_title'],
        'news_posts'          => ['title', 'excerpt', 'body', 'meta_title', 'meta_description'],
        'news_categories'     => ['name', 'description'],
        'jobs'                => ['title', 'description'],
        // The Authority's own tables. Every one of these columns is a locale
        // map an officer authors in English; the dictionary completes the
        // Sinhala and Tamil so a record is not published in one language only.
        'notices'             => ['title', 'body'],
        'services'            => ['title', 'summary', 'eligibility', 'fee', 'duration', 'division', 'contact_point'],
        'document_categories' => ['name'],
        'documents'           => ['title', 'description'],
        'faqs'                => ['question', 'answer'],
        'offices'             => ['name', 'address'],
        'staff'               => ['designation', 'division', 'subject_area'],
        'statistics_datasets' => ['title', 'description', 'unit', 'source'],
        'programmes'          => ['title', 'summary', 'description', 'audience', 'fee', 'venue'],
        'discussion_topics'   => ['title', 'body'],
        'org_links'           => ['name'],
    ];

    private array $dict;

    public function __construct()
    {
        $file       = ROOTPATH . 'modules/Core/Config/ContentTranslations.php';
        $this->dict = is_file($file) ? (require $file) : [];
    }

    /** @return array{blocks:int,rows:int} counts of updated records */
    public function backfill(): array
    {
        if ($this->dict === []) {
            return ['blocks' => 0, 'rows' => 0];
        }

        return ['blocks' => $this->backfillBlocks(), 'rows' => $this->backfillTables()];
    }

    /** Page-builder blocks: locale maps can be nested anywhere in the content JSON. */
    private function backfillBlocks(): int
    {
        $db      = db_connect();
        $updated = 0;

        try {
            $rows = $db->table('page_blocks')->select('id, content')->get()->getResultArray();
        } catch (\Throwable $e) {
            return 0;
        }

        foreach ($rows as $row) {
            $data = json_decode((string) $row['content'], true);
            if (! is_array($data)) {
                continue;
            }
            $changed = false;
            $this->walk($data, $changed);
            if ($changed) {
                $db->table('page_blocks')->where('id', $row['id'])
                    ->update(['content' => json_encode($data, JSON_UNESCAPED_UNICODE)]);
                $updated++;
            }
        }

        return $updated;
    }

    private function backfillTables(): int
    {
        $db      = db_connect();
        $updated = 0;

        // Ask the schema first, rather than querying and catching. The catch
        // below still runs the query and CodeIgniter logs the failure before
        // throwing, so a table this install does not have wrote fourteen lines
        // of stack trace to the application log on every seed. Eleven of the
        // tables named above belong to the site this one was forked from, so
        // that was the whole log: a hundred and fifty lines of expected,
        // handled, deliberately-ignored errors, in the file somebody reads to
        // find out why a page is 500ing.
        //
        // The try/catch stays, because tableExists() answers a narrower
        // question than the catch does — a table that exists without one of
        // these columns still throws, and still should be skipped.
        $present = array_flip(array_map('strtolower', $db->listTables() ?: []));

        foreach (self::COLUMNS as $table => $columns) {
            if (! isset($present[strtolower($db->prefixTable($table))])) {
                continue; // not a table in this install
            }

            try {
                $rows = $db->table($table)->select('id, ' . implode(', ', $columns))->get()->getResultArray();
            } catch (\Throwable $e) {
                continue; // the table is here but a column is not
            }

            foreach ($rows as $row) {
                $patch = [];
                foreach ($columns as $col) {
                    $map = json_decode((string) ($row[$col] ?? ''), true);
                    if (! is_array($map) || ! isset($map['en'])) {
                        continue;
                    }
                    $filled = $this->fill($map);
                    if ($filled !== $map) {
                        $patch[$col] = json_encode($filled, JSON_UNESCAPED_UNICODE);
                    }
                }
                if ($patch !== []) {
                    $db->table($table)->where('id', $row['id'])->update($patch);
                    $updated++;
                }
            }
        }

        return $updated;
    }

    /** Recursively fill every locale map found in a decoded structure. */
    private function walk(array &$node, bool &$changed): void
    {
        if ($this->isLocaleMap($node)) {
            $filled = $this->fill($node);
            if ($filled !== $node) {
                $node    = $filled;
                $changed = true;
            }
            return;
        }

        foreach ($node as &$child) {
            if (is_array($child)) {
                $this->walk($child, $changed);
            }
        }
    }

    /**
     * A locale map is any node keyed purely by language tags with an English
     * side. Matching the shape rather than a fixed list means content written
     * under a locale the site has since dropped is still recognised — which is
     * what lets it be migrated instead of silently ignored.
     */
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

    private function fill(array $map): array
    {
        $en = trim((string) ($map['en'] ?? ''));
        if ($en === '' || ! isset($this->dict[$en])) {
            return $map;
        }

        foreach (translatable_locales() as $locale) {
            if (isset($this->dict[$en][$locale]) && trim((string) ($map[$locale] ?? '')) === '') {
                $map[$locale] = $this->dict[$en][$locale];
            }
        }

        return $map;
    }
}
