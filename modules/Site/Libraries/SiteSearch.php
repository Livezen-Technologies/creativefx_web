<?php

namespace Modules\Site\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Search across everything a visitor can reach.
 *
 * One library rather than a search per section, because a reader typing
 * "photoshop" wants the course, the next date, the guide about it and the
 * cheat sheet — and does not know or care that those live in four tables.
 *
 * Deliberately SQL rather than a search engine. Meilisearch would rank better,
 * and the blueprint names it; it is also a second service to run, monitor,
 * back up and keep in step with the database, for a catalogue of a few hundred
 * rows on a site that has just launched. The seam is here: everything goes
 * through `search()`, so replacing the inside of this class with an index is a
 * contained change rather than a rewrite of six controllers.
 *
 * Two things it does that a naive LIKE does not:
 *
 *   **It searches every language at once.** Titles and summaries are JSON
 *   locale maps in one column, so a Sinhala query matches the Sinhala inside
 *   the same field. That is a happy accident of the storage format, and it is
 *   the right behaviour.
 *
 *   **It falls back from the phrase to its words.** "when is the next photoshop
 *   class" matches nothing as a phrase and everything as a word list. Without
 *   the fallback, natural-language questions — which is how people search, and
 *   how the help assistant asks — return nothing at all.
 */
class SiteSearch
{
    /** Shortest query worth running. Two letters matches half the catalogue. */
    private const MIN_LENGTH = 3;

    /** Words too common to narrow anything, dropped from the word-list pass. */
    private const STOP_WORDS = [
        'the', 'a', 'an', 'and', 'or', 'of', 'for', 'to', 'in', 'on', 'at', 'is',
        'are', 'was', 'were', 'be', 'do', 'does', 'how', 'what', 'when', 'where',
        'which', 'who', 'why', 'can', 'i', 'my', 'me', 'you', 'your', 'it', 'its',
        'with', 'from', 'about', 'next', 'course', 'courses', 'class', 'classes',
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @return array{results:list<array{type:string,title:string,url:string,snippet:string}>, total:int, terms:list<string>}
     */
    public function search(string $query, int $perPage = 20, int $page = 1): array
    {
        helper(['norlanka', 'url', 'catalog']);

        $query = trim($query);
        if (mb_strlen($query) < self::MIN_LENGTH) {
            return ['results' => [], 'total' => 0, 'terms' => []];
        }

        // The whole phrase first: an exact match is what somebody who typed a
        // course name deserves at the top.
        $results = $this->collect([$query]);

        $terms = [$query];
        if ($results === []) {
            $terms = $this->keywords($query);
            if ($terms !== []) {
                $results = $this->collect($terms);
            }
        }

        $total = count($results);
        $slice = array_slice($results, max(0, ($page - 1) * $perPage), $perPage);

        return ['results' => $slice, 'total' => $total, 'terms' => $terms];
    }

    /**
     * The content words in a question.
     *
     * @return list<string>
     */
    public function keywords(string $query): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $words,
            static fn (string $w): bool => mb_strlen($w) >= 3 && ! in_array($w, self::STOP_WORDS, true)
        ));
    }

    /**
     * Run every source against a set of terms and merge the results.
     *
     * Ordered by source rather than by relevance: a course is a better answer
     * than an article that mentions it, whatever a LIKE thinks, and a genuine
     * relevance score is the thing a real index would bring.
     *
     * @param list<string> $terms
     * @return list<array>
     */
    private function collect(array $terms): array
    {
        $out  = [];
        $seen = [];

        foreach ([
            $this->courses($terms),
            $this->bundles($terms),
            $this->pages($terms),
            $this->posts($terms),
            $this->resources($terms),
            $this->instructors($terms),
        ] as $group) {
            foreach ($group as $row) {
                // The same course can match on its title and its summary. A
                // result list with one entry twice reads as a bug.
                if (isset($seen[$row['url']])) {
                    continue;
                }
                $seen[$row['url']] = true;
                $out[]             = $row;
            }
        }

        return $out;
    }

    /** @param list<string> $terms */
    private function courses(array $terms): array
    {
        $rows = $this->matchAny('courses', ['title', 'summary', 'description', 'slug'], $terms, [
            'status'          => 'published',
            'deleted_at IS NULL' => null,
        ], 'id, slug, title, summary');

        return array_map(static fn (array $r): array => [
            'type'    => lang('Catalog.courses.title'),
            'title'   => t_field($r['title']),
            'url'     => course_url($r['slug']),
            'snippet' => mb_substr(strip_tags(t_field($r['summary'])), 0, 180),
        ], $rows);
    }

    /** @param list<string> $terms */
    private function bundles(array $terms): array
    {
        $rows = $this->matchAny('bundles', ['title', 'summary', 'description', 'slug'], $terms, [
            'status' => 'published',
        ], 'id, slug, type, title, summary');

        return array_map(static fn (array $r): array => [
            'type'    => $r['type'] === 'bootcamp' ? lang('Catalog.bundles.bootcamps_title') : lang('Catalog.bundles.certificates_title'),
            'title'   => t_field($r['title']),
            'url'     => locale_url(($r['type'] === 'bootcamp' ? 'bootcamps/' : 'certificates/') . $r['slug']),
            'snippet' => mb_substr(strip_tags(t_field($r['summary'])), 0, 180),
        ], $rows);
    }

    /** @param list<string> $terms */
    private function pages(array $terms): array
    {
        $rows = $this->matchAny('pages', ['title', 'meta_description', 'slug'], $terms, [
            'status'             => 'published',
            'deleted_at IS NULL' => null,
        ], 'id, slug, title, meta_description');

        return array_map(static fn (array $r): array => [
            'type'    => lang('Site.search.type_page'),
            'title'   => t_field($r['title']),
            'url'     => locale_url($r['slug']),
            'snippet' => mb_substr(strip_tags(t_field($r['meta_description'])), 0, 180),
        ], $rows);
    }

    /** @param list<string> $terms */
    private function posts(array $terms): array
    {
        $rows = $this->matchAny('news_posts', ['title', 'excerpt', 'body', 'slug'], $terms, [
            'status' => 'published',
        ], 'id, slug, title, excerpt');

        return array_map(static fn (array $r): array => [
            'type'    => lang('Site.news.title'),
            'title'   => t_field($r['title']),
            'url'     => locale_url('blog/' . $r['slug']),
            'snippet' => mb_substr(strip_tags(t_field($r['excerpt'])), 0, 180),
        ], $rows);
    }

    /** @param list<string> $terms */
    private function resources(array $terms): array
    {
        $rows = $this->matchAny('resources', ['title', 'summary', 'body', 'slug'], $terms, [
            'status' => 'published',
        ], 'id, slug, title, summary');

        return array_map(static fn (array $r): array => [
            'type'    => lang('Catalog.resources.title'),
            'title'   => t_field($r['title']),
            'url'     => locale_url('resources/' . $r['slug']),
            'snippet' => mb_substr(strip_tags(t_field($r['summary'])), 0, 180),
        ], $rows);
    }

    /** @param list<string> $terms */
    private function instructors(array $terms): array
    {
        $rows = $this->matchAny('instructors', ['name', 'headline', 'bio'], $terms, [
            'status' => 'published',
        ], 'id, slug, name, headline');

        return array_map(static fn (array $r): array => [
            'type'    => lang('Catalog.instructors.title'),
            'title'   => $r['name'],
            'url'     => locale_url('instructors/' . $r['slug']),
            'snippet' => mb_substr(strip_tags(t_field($r['headline'])), 0, 180),
        ], $rows);
    }

    /**
     * Rows where any of the columns contains any of the terms.
     *
     * The table is checked for existence first: search runs on the 404 page and
     * in the help assistant, and a missing table before migrations have run
     * should cost a result group, not the page.
     *
     * @param list<string> $columns
     * @param list<string> $terms
     * @param array<string, mixed> $where  a null value means the key is raw SQL
     * @return list<array>
     */
    private function matchAny(string $table, array $columns, array $terms, array $where, string $select): array
    {
        if (! $this->db->tableExists($table)) {
            return [];
        }

        $builder = $this->db->table($table)->select($select);

        foreach ($where as $key => $value) {
            $value === null ? $builder->where($key) : $builder->where($key, $value);
        }

        $builder->groupStart();
        $first = true;
        foreach ($terms as $term) {
            foreach ($columns as $column) {
                $first ? $builder->like($column, $term) : $builder->orLike($column, $term);
                $first = false;
            }
        }
        $builder->groupEnd();

        return $builder->limit(30)->get()->getResultArray();
    }
}
