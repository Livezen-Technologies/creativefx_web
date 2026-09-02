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
        'pages'               => ['title', 'meta_description'],
        'products'            => ['name', 'short_description', 'description', 'meta_title', 'meta_description'],
        'product_categories'  => ['name', 'description'],
        'news_posts'          => ['title', 'excerpt', 'body', 'meta_title', 'meta_description'],
        'news_categories'     => ['name', 'description'],
        'jobs'                => ['title', 'description'],
        'showroom_products'   => ['name', 'description'],
        'showroom_categories' => ['name'],
        'esg_metrics'         => ['label'],
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

        foreach (self::COLUMNS as $table => $columns) {
            try {
                $rows = $db->table($table)->select('id, ' . implode(', ', $columns))->get()->getResultArray();
            } catch (\Throwable $e) {
                continue; // table or column not present in this install
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
