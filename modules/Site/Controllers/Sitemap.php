<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use Throwable;

/**
 * /sitemap.xml — every published URL, in every supported locale, with hreflang
 * alternates so search engines pair the translations rather than treating them
 * as duplicates.
 *
 * Generated on request rather than written to disk: the site is small enough
 * that the queries are cheap, and a file would go stale the moment someone
 * published a page in the admin.
 */
class Sitemap extends BaseController
{
    public function index()
    {
        helper(['url', 'norlanka']);

        $locales = config('App')->supportedLocales;
        $urls    = [];

        // Slug => change frequency / priority. '' is the home page.
        foreach ($this->slugs() as $slug => [$freq, $priority, $lastmod]) {
            $alternates = [];
            foreach ($locales as $locale) {
                $alternates[$locale] = rtrim(site_url($locale . ($slug === '' ? '' : '/' . $slug)), '/');
            }

            foreach ($locales as $locale) {
                $urls[] = [
                    'loc'        => $alternates[$locale],
                    'changefreq' => $freq,
                    'priority'   => $priority,
                    'lastmod'    => $lastmod,
                    'alternates' => $alternates,
                ];
            }
        }

        return $this->response
            ->setContentType('application/xml')
            ->setBody($this->xml($urls));
    }

    /**
     * Built as a string rather than rendered through a view: CI4 wraps rendered
     * views in <!-- DEBUG-VIEW --> comments while debugging, which is harmless
     * in HTML and fatal in XML.
     *
     * @param list<array{loc:string,changefreq:string,priority:string,lastmod:?string,alternates:array<string,string>}> $urls
     */
    private function xml(array $urls): string
    {
        $e   = static fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($urls as $url) {
            $out .= "  <url>\n";
            $out .= '    <loc>' . $e($url['loc']) . "</loc>\n";
            if (! empty($url['lastmod'])) {
                $out .= '    <lastmod>' . $e($url['lastmod']) . "</lastmod>\n";
            }
            $out .= '    <changefreq>' . $e($url['changefreq']) . "</changefreq>\n";
            $out .= '    <priority>' . $e($url['priority']) . "</priority>\n";
            foreach ($url['alternates'] as $locale => $href) {
                $out .= '    <xhtml:link rel="alternate" hreflang="' . $e((string) $locale) . '" href="' . $e($href) . '"/>' . "\n";
            }
            $out .= "  </url>\n";
        }

        return $out . '</urlset>' . "\n";
    }

    /**
     * @return array<string, array{0:string,1:string,2:?string}>
     */
    private function slugs(): array
    {
        $slugs = [
            ''          => ['weekly', '1.0', null],
            'portfolio' => ['weekly', '0.9', null],
            'quote'     => ['monthly', '0.8', null],
        ];

        // Published CMS pages (this already covers our-story, services and the
        // six services/<slug> pages, since each is a page row).
        try {
            $pages = model('Modules\Cms\Models\PageModel')
                ->where('status', 'published')
                ->where('is_home', 0)
                ->findAll();

            foreach ($pages as $page) {
                $slug = trim((string) ($page['slug'] ?? ''), '/');
                if ($slug === '') {
                    continue;
                }
                $slugs[$slug] = ['monthly', str_contains($slug, '/') ? '0.8' : '0.7', $this->day($page['updated_at'] ?? null)];
            }
        } catch (Throwable $e) {
            // A sitemap missing a section beats a 500 on /sitemap.xml.
        }

        try {
            $projects = model('Modules\Portfolio\Models\PortfolioProjectModel')
                ->where('status', 'published')
                ->findAll();

            foreach ($projects as $project) {
                $slugs['portfolio/' . $project['slug']] = ['monthly', '0.6', $this->day($project['updated_at'] ?? null)];
            }
        } catch (Throwable $e) {
            // Portfolio not migrated yet.
        }

        return $slugs;
    }

    /** W3C date (YYYY-MM-DD) from a DATETIME, or null when absent. */
    private function day($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : substr($value, 0, 10);
    }
}
