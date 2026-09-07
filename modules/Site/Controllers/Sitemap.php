<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * The sitemaps — the XML ones a crawler fetches, and the human one a reader
 * uses when the menu has failed them.
 *
 * Split by type, as the SEO plan requires. One sitemap containing courses,
 * every scheduled date, every article and every page is a file that changes
 * whenever anything changes, so a crawler that re-fetches it learns nothing
 * about what actually moved. Split, a new class date changes only
 * `sitemap-sessions.xml`, and the index's `lastmod` says so.
 *
 * `lastmod` is read from the row rather than stamped with today's date. A
 * sitemap that claims every URL changed this morning is a sitemap a search
 * engine stops believing, and it is the single most common way the file makes
 * things worse rather than better.
 *
 * Every URL is emitted once per locale with `xhtml:link` alternates, which is
 * what tells a search engine that /en/course/x and /si/course/x are the same
 * page in two languages rather than duplicates competing with each other.
 */
class Sitemap extends BaseController
{
    /** The sections, in the order the index lists them. */
    private const SECTIONS = ['pages', 'courses', 'sessions', 'bundles', 'posts', 'resources', 'people'];

    /** A sitemap file may hold 50,000 URLs; this is the practical cap. */
    private const MAX_URLS = 5000;

    /** The index. */
    public function index()
    {
        helper('url');

        $sections = [];
        foreach (self::SECTIONS as $section) {
            $latest = $this->latestFor($section);
            if ($latest === null) {
                continue;   // nothing published in this section yet
            }
            $sections[] = [
                'loc'     => rtrim(base_url(), '/') . '/sitemap-' . $section . '.xml',
                'lastmod' => $latest,
            ];
        }

        return $this->xml(view('Modules\Site\Views\sitemap_index', ['sections' => $sections], ['saveData' => false]));
    }

    /** One section. */
    public function section(?string $name = null)
    {
        helper(['url', 'norlanka']);

        if (! in_array($name, self::SECTIONS, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->xml(view('Modules\Site\Views\sitemap', [
            'urls'    => $this->urlsFor((string) $name),
            'locales' => config('App')->supportedLocales,
        ], ['saveData' => false]));
    }

    /**
     * The human sitemap: every section of the site, as a page.
     *
     * Worth keeping even in a site with good navigation. It is where somebody
     * lands from a 404, it is the page a screen-reader user reaches for when a
     * menu is fighting them, and it is a page of internal links a crawler can
     * follow in one go.
     */
    public function page(?string $locale = null)
    {
        helper(['url', 'norlanka', 'catalog']);

        $db = db_connect();

        $categories = $db->tableExists('course_categories')
            ? $db->table('course_categories')->where('status', 'published')
                ->orderBy('sort_order', 'ASC')->get()->getResultArray()
            : [];

        $pages = $db->tableExists('pages')
            ? $db->table('pages')->select('slug, title')->where('status', 'published')
                ->where('deleted_at IS NULL')->orderBy('sort_order', 'ASC')->get()->getResultArray()
            : [];

        return view('Modules\Site\Views\sitemap_page', [
            'categories'      => $categories,
            'pages'           => $pages,
            'crumbs'          => [['label' => lang('Site.sitemap.title')]],
            'title'           => lang('Site.sitemap.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.sitemap.meta'),
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * The URLs in one section, each with the date its own row last changed.
     *
     * @return list<array{path:string, lastmod:?string, priority:string}>
     */
    private function urlsFor(string $section): array
    {
        $db = db_connect();

        switch ($section) {
            case 'pages':
                $urls = [
                    ['path' => '', 'lastmod' => null, 'priority' => '1.0'],
                    ['path' => 'courses', 'lastmod' => null, 'priority' => '0.9'],
                    ['path' => 'schedule', 'lastmod' => null, 'priority' => '0.9'],
                    ['path' => 'adobe', 'lastmod' => null, 'priority' => '0.8'],
                    ['path' => 'ai', 'lastmod' => null, 'priority' => '0.8'],
                    ['path' => 'adobe/certification', 'lastmod' => null, 'priority' => '0.7'],
                    ['path' => 'certificates', 'lastmod' => null, 'priority' => '0.7'],
                    ['path' => 'bootcamps', 'lastmod' => null, 'priority' => '0.7'],
                    ['path' => 'on-demand', 'lastmod' => null, 'priority' => '0.7'],
                    ['path' => 'corporate', 'lastmod' => null, 'priority' => '0.7'],
                    ['path' => 'locations', 'lastmod' => null, 'priority' => '0.6'],
                    ['path' => 'instructors', 'lastmod' => null, 'priority' => '0.6'],
                    ['path' => 'resources', 'lastmod' => null, 'priority' => '0.6'],
                    ['path' => 'webinars', 'lastmod' => null, 'priority' => '0.5'],
                    ['path' => 'reviews', 'lastmod' => null, 'priority' => '0.5'],
                    ['path' => 'blog', 'lastmod' => null, 'priority' => '0.6'],
                    ['path' => 'careers', 'lastmod' => null, 'priority' => '0.3'],
                    ['path' => 'sitemap', 'lastmod' => null, 'priority' => '0.3'],
                ];

                foreach ($this->rows('pages', 'slug, updated_at', ['status' => 'published', 'deleted_at IS NULL' => null]) as $row) {
                    $urls[] = ['path' => $row['slug'], 'lastmod' => $row['updated_at'], 'priority' => '0.6'];
                }
                foreach ($this->rows('course_categories', 'slug, updated_at', ['status' => 'published']) as $row) {
                    $urls[] = ['path' => 'courses/' . $row['slug'], 'lastmod' => $row['updated_at'], 'priority' => '0.7'];
                }

                return $urls;

            case 'courses':
                return array_map(static fn (array $r): array => [
                    'path' => 'course/' . $r['slug'], 'lastmod' => $r['updated_at'], 'priority' => '0.9',
                ], $this->rows('courses', 'slug, updated_at', ['status' => 'published', 'deleted_at IS NULL' => null]));

            case 'sessions':
                // Only sessions that are still ahead. A sitemap full of dates
                // that have already run is a sitemap of pages a crawler will
                // find, index and then have to forget.
                $rows = $db->tableExists('course_sessions')
                    ? $db->table('course_sessions cs')
                        ->select('cs.id, cs.updated_at, c.slug')
                        ->join('courses c', 'c.id = cs.course_id')
                        ->whereIn('cs.status', ['open', 'confirmed', 'waitlist'])
                        ->where('cs.is_private', 0)
                        ->where('cs.start_date >=', date('Y-m-d'))
                        ->where('c.status', 'published')->where('c.deleted_at IS NULL')
                        ->limit(self::MAX_URLS)->get()->getResultArray()
                    : [];

                return array_map(static fn (array $r): array => [
                    'path' => 'schedule/' . $r['slug'] . '-' . (int) $r['id'],
                    'lastmod' => $r['updated_at'], 'priority' => '0.6',
                ], $rows);

            case 'bundles':
                return array_map(static fn (array $r): array => [
                    'path' => ($r['type'] === 'bootcamp' ? 'bootcamps/' : 'certificates/') . $r['slug'],
                    'lastmod' => $r['updated_at'], 'priority' => '0.8',
                ], $this->rows('bundles', 'slug, type, updated_at', ['status' => 'published']));

            case 'posts':
                return array_map(static fn (array $r): array => [
                    'path' => 'blog/' . $r['slug'], 'lastmod' => $r['updated_at'], 'priority' => '0.6',
                ], $this->rows('news_posts', 'slug, updated_at', ['status' => 'published']));

            case 'resources':
                $out = array_map(static fn (array $r): array => [
                    'path' => 'resources/' . $r['slug'], 'lastmod' => $r['updated_at'], 'priority' => '0.6',
                ], $this->rows('resources', 'slug, updated_at', ['status' => 'published']));

                foreach ($this->rows('webinars', 'slug, updated_at', ['status' => 'published']) as $row) {
                    $out[] = ['path' => 'webinars/' . $row['slug'], 'lastmod' => $row['updated_at'], 'priority' => '0.5'];
                }

                return $out;

            case 'people':
                $out = array_map(static fn (array $r): array => [
                    'path' => 'instructors/' . $r['slug'], 'lastmod' => $r['updated_at'], 'priority' => '0.5',
                ], $this->rows('instructors', 'slug, updated_at', ['status' => 'published']));

                foreach ($this->rows('venues', 'slug, updated_at', ['status' => 'published']) as $row) {
                    $out[] = ['path' => 'locations/' . $row['slug'], 'lastmod' => $row['updated_at'], 'priority' => '0.6'];
                }

                return $out;
        }

        return [];
    }

    /**
     * Rows from a table that may not exist yet.
     *
     * The sitemap is fetched by crawlers within minutes of a domain resolving,
     * which can be before the first migration has run. A missing table should
     * cost a section, not a 500 in a robot's log.
     *
     * @param array<string, mixed> $where a null value means the key is raw SQL
     * @return list<array>
     */
    private function rows(string $table, string $select, array $where): array
    {
        $db = db_connect();
        if (! $db->tableExists($table)) {
            return [];
        }

        $builder = $db->table($table)->select($select);
        foreach ($where as $key => $value) {
            $value === null ? $builder->where($key) : $builder->where($key, $value);
        }

        return $builder->limit(self::MAX_URLS)->get()->getResultArray();
    }

    /** The most recent change in a section, for the index's lastmod. */
    private function latestFor(string $section): ?string
    {
        $urls = $this->urlsFor($section);
        if ($urls === []) {
            return null;
        }

        $dates = array_filter(array_column($urls, 'lastmod'));

        return $dates === [] ? date('Y-m-d') : substr((string) max($dates), 0, 10);
    }

    private function xml(string $body)
    {
        return $this->response
            ->setContentType('application/xml')
            ->setBody($body);
    }
}
