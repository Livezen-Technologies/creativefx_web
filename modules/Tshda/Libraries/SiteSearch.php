<?php

namespace Modules\Tshda\Libraries;

/**
 * Site-wide search (Clause 3.12).
 *
 * The clause sets specific minimums: find everything on the site — page
 * content, news, documents, directory entries and gallery metadata — return
 * results sortable by relevance, date, content type, language and division,
 * link to related audio and video in the media library, search inside PDFs
 * rather than only their file names, paginate, and normalise Sinhala and Tamil
 * so that variant orthography returns consistent results.
 *
 * The implementation is deliberately a set of queries against the same
 * database the site renders from, rather than a separate index. An index is
 * faster and is the right answer at a much larger scale; it is also a second
 * copy of the truth that goes stale when a seeder runs, a document is archived
 * or a translation lands, and on a site of this size the query is fast enough
 * that the staleness would be the only thing the index bought us.
 */
class SiteSearch
{
    /** Search each source, then rank everything together. */
    public function run(string $query, string $locale = 'en'): array
    {
        $needle = self::normalise($query);
        if ($needle === '') {
            return [];
        }

        $results = array_merge(
            $this->pages($query),
            $this->news($query),
            $this->documents($query),
            $this->services($query),
            $this->staff($query),
            $this->faqs($query),
            $this->media($query),
        );

        foreach ($results as &$row) {
            $row['score'] = self::score($row, $needle);
        }
        unset($row);

        return $results;
    }

    /**
     * Fold a string down to something two spellings of the same word share.
     *
     * Sinhala and Tamil are where this earns its place. Sinhala writes many
     * words with or without the ZWJ that binds a conjunct, and with either of
     * two visually identical yansaya/rakaransaya encodings; Tamil text arrives
     * with and without the combining forms that some keyboards emit. A reader
     * types what their keyboard gives them and expects to find text somebody
     * else typed on a different one. Stripping the joiners, the variation
     * selectors and the diacritics — and then case-folding, which is a no-op
     * for both scripts and matters for English — makes those spellings equal.
     */
    public static function normalise(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        // Zero-width joiner / non-joiner / zero-width space / BOM, and the
        // variation selectors. Invisible, and different on every keyboard.
        $text = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{FE00}-\x{FE0F}]/u', '', $text);

        if (class_exists(\Normalizer::class)) {
            // NFKD splits a composed character into its base plus its marks, so
            // the marks can be dropped uniformly rather than one composition at
            // a time.
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KD) ?: $text;
        }

        // Latin diacritics only. Sinhala and Tamil vowel signs are letters, not
        // accents — stripping them would turn different words into one.
        $text = preg_replace('/[\x{0300}-\x{036F}]/u', '', $text);

        $text = preg_replace('/\s+/u', ' ', $text);

        return mb_strtolower(trim($text));
    }

    /** @return array<string,int> */
    public static function countByType(array $results): array
    {
        $counts = [];
        foreach ($results as $row) {
            $counts[$row['type']] = ($counts[$row['type']] ?? 0) + 1;
        }
        arsort($counts);

        return $counts;
    }

    public static function sort(array $results, string $sort): array
    {
        usort($results, static function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'date'  => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')),
                'title' => strcmp((string) $a['title'], (string) $b['title']),
                'type'  => strcmp((string) $a['type'], (string) $b['type']) ?: ($b['score'] <=> $a['score']),
                default => ($b['score'] <=> $a['score']) ?: strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')),
            };
        });

        return $results;
    }

    /**
     * How well one result answers the query.
     *
     * A title match beats a body match, an exact phrase beats scattered words,
     * and a result that contains every word beats one that contains some. That
     * is the whole model — deliberately simple, and explainable to an officer
     * who asks why a particular circular came third.
     */
    private static function score(array $row, string $needle): int
    {
        $title = self::normalise((string) $row['title']);
        $body  = self::normalise((string) ($row['excerpt'] ?? ''));
        $score = 0;

        if ($title === $needle) {
            $score += 1000;
        }
        if (str_contains($title, $needle)) {
            $score += 300;
        }
        if (str_contains($body, $needle)) {
            $score += 60;
        }

        $words = array_filter(explode(' ', $needle));
        foreach ($words as $word) {
            if (mb_strlen($word) < 2) {
                continue;
            }
            if (str_contains($title, $word)) {
                $score += 40;
            }
            if (str_contains($body, $word)) {
                $score += 8;
            }
        }

        return $score + (int) ($row['weight'] ?? 0);
    }

    private function db()
    {
        return \Config\Database::connect();
    }

    /** A locale-map column, likened against — the JSON holds every language. */
    private function like($builder, array $columns, string $query)
    {
        $builder->groupStart();
        foreach ($columns as $i => $column) {
            $i === 0 ? $builder->like($column, $query) : $builder->orLike($column, $query);
        }

        return $builder->groupEnd();
    }

    private function rows(string $table, array $columns, string $query, array $where = [], int $limit = 50): array
    {
        try {
            $builder = $this->db()->table($table);
            foreach ($where as $k => $v) {
                $builder->where($k, $v);
            }
            $this->like($builder, $columns, $query);

            return $builder->limit($limit)->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function excerpt(?string $json, int $length = 220): string
    {
        helper('norlanka');
        $text = strip_tags(t_field($json ?? ''));

        return mb_substr(preg_replace('/\s+/u', ' ', $text), 0, $length);
    }

    private function pages(string $query): array
    {
        helper(['norlanka', 'url']);
        $out = [];

        // Page bodies live in the block JSON, so the blocks are searched and
        // the page they belong to is what gets returned — a hit on a paragraph
        // is a hit on the page that carries it, not on a block number.
        $pageIds = [];
        foreach ($this->rows('page_blocks', ['content'], $query, [], 200) as $block) {
            $section = $this->db()->table('page_sections')->where('id', $block['section_id'])->get()->getRowArray();
            if ($section !== null) {
                $pageIds[(int) $section['page_id']] = true;
            }
        }

        foreach ($this->rows('pages', ['title', 'meta_description'], $query, ['status' => 'published']) as $page) {
            $pageIds[(int) $page['id']] = true;
        }

        foreach (array_keys($pageIds) as $id) {
            $page = $this->db()->table('pages')->where('id', $id)->where('status', 'published')->get()->getRowArray();
            if ($page === null) {
                continue;
            }
            $out[] = [
                'type'    => 'page',
                'title'   => t_field($page['title']),
                'excerpt' => $this->excerpt($page['meta_description']),
                'url'     => locale_url((int) $page['is_home'] === 1 ? '' : $page['slug']),
                'date'    => $page['updated_at'] ?? null,
                'weight'  => 20,
            ];
        }

        return $out;
    }

    private function news(string $query): array
    {
        helper(['norlanka', 'url']);
        $out = [];

        foreach ($this->rows('news_posts', ['title', 'excerpt', 'body'], $query, ['status' => 'published']) as $post) {
            $out[] = [
                'type'    => 'news',
                'title'   => t_field($post['title']),
                'excerpt' => $this->excerpt($post['excerpt'] ?: $post['body']),
                'url'     => locale_url('news/' . $post['slug']),
                'date'    => $post['published_at'] ?? $post['created_at'] ?? null,
                'weight'  => 5,
            ];
        }

        return $out;
    }

    private function documents(string $query): array
    {
        helper(['norlanka', 'url']);
        $out = [];

        // extracted_text is the text pulled out of the file at upload. It is
        // what makes a phrase inside a circular findable rather than only the
        // circular's file name — the specific thing Clause 3.12 asks for.
        foreach ($this->rows('documents', ['title', 'description', 'extracted_text'], $query, ['status' => 'published']) as $doc) {
            $out[] = [
                'type'    => 'document',
                'title'   => t_field($doc['title']),
                'excerpt' => $this->excerpt($doc['description']),
                'url'     => locale_url('downloads/' . $doc['slug']),
                'date'    => $doc['published_at'] ?? null,
                'meta'    => strtoupper((string) $doc['file_type']),
                'weight'  => 10,
            ];
        }

        return $out;
    }

    private function services(string $query): array
    {
        helper(['norlanka', 'url']);
        $out = [];

        foreach ($this->rows('services', ['title', 'summary', 'eligibility'], $query, ['status' => 'published']) as $row) {
            $out[] = [
                'type'    => 'service',
                'title'   => t_field($row['title']),
                'excerpt' => $this->excerpt($row['summary']),
                'url'     => locale_url('services/' . $row['slug']),
                'date'    => $row['updated_at'] ?? null,
                'weight'  => 30,
            ];
        }

        return $out;
    }

    private function staff(string $query): array
    {
        helper(['norlanka', 'url']);
        $out = [];

        foreach ($this->rows('staff', ['name', 'designation', 'division', 'subject_area'], $query, ['status' => 'published']) as $row) {
            $out[] = [
                'type'    => 'directory',
                'title'   => $row['name'] . ' — ' . t_field($row['designation']),
                'excerpt' => trim(t_field($row['division']) . ' · ' . t_field($row['subject_area']), ' ·'),
                'url'     => locale_url('directory') . '?q=' . rawurlencode($row['name']),
                'date'    => $row['updated_at'] ?? null,
                'weight'  => 0,
            ];
        }

        return $out;
    }

    private function faqs(string $query): array
    {
        helper(['norlanka', 'url']);
        $out = [];

        foreach ($this->rows('faqs', ['question', 'answer'], $query, ['status' => 'published']) as $row) {
            $out[] = [
                'type'    => 'faq',
                'title'   => t_field($row['question']),
                'excerpt' => $this->excerpt($row['answer']),
                'url'     => locale_url('faqs') . '#faq-' . $row['id'],
                'date'    => $row['updated_at'] ?? null,
                'weight'  => 0,
            ];
        }

        return $out;
    }

    /**
     * Gallery metadata and the media library's audio and video, which the
     * clause asks search to reach as well as the written content.
     */
    private function media(string $query): array
    {
        helper(['norlanka', 'url']);
        $out = [];

        foreach ($this->rows('videos', ['title'], $query, [], 25) as $row) {
            $out[] = [
                'type'    => 'video',
                'title'   => t_field($row['title'] ?? ''),
                'excerpt' => '',
                'url'     => locale_url('videos'),
                'date'    => $row['updated_at'] ?? null,
                'weight'  => 0,
            ];
        }

        foreach ($this->rows('media', ['alt', 'original_name', 'tags'], $query, [], 25) as $row) {
            $title = trim((string) ($row['alt'] ?? '')) ?: (string) ($row['original_name'] ?? '');
            if ($title === '') {
                continue;
            }
            $out[] = [
                'type'    => 'image',
                'title'   => $title,
                'excerpt' => (string) ($row['tags'] ?? ''),
                'url'     => locale_url('gallery'),
                'date'    => $row['created_at'] ?? null,
                'weight'  => 0,
            ];
        }

        return $out;
    }
}
