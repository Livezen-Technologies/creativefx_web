<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use Config\App;
use Modules\Careers\Models\JobModel;
use Modules\Cms\Models\PageModel;
use Modules\News\Models\NewsPostModel;
use Modules\Tshda\Models\DiscussionTopicModel;
use Modules\Tshda\Models\ProgrammeModel;
use Modules\Tshda\Models\ServiceModel;
use Modules\Tshda\Models\StatisticModel;

/**
 * The XML sitemap, built from the pages table rather than a hand-kept list.
 *
 * A file would go stale the first time somebody adds a page in the admin
 * console, which is the one thing this site is now meant to support. Reading
 * the same query the router honours means the sitemap cannot advertise a draft,
 * a scheduled page before its date, or a slug that has been deleted.
 *
 * Every page is listed once per language, and each entry names its siblings
 * through xhtml:link alternates. That is what tells a search engine these are
 * translations of one page rather than several thin pages saying similar
 * things — and it is why the file is not simply a list of URLs.
 *
 * Most of this site is not in the pages table, though. The service catalogue,
 * the statistics, the training calendar, the newsroom and the vacancies are
 * their own records with their own addresses, and a sitemap listing only the
 * editorial pages would leave the majority of the Authority's content
 * undiscoverable. Each of those is added below, from the same query the public
 * controller uses — so a closed vacancy or a draft dataset is absent here for
 * the same reason it is absent from the site.
 */
class Sitemap extends BaseController
{
    public function index()
    {
        $locales = config(App::class)->supportedLocales;
        $pages   = (new PageModel())->findAllPublished();

        $entries = [];
        foreach ($pages as $page) {
            $isHome = (int) ($page['is_home'] ?? 0) === 1 || ($page['template'] ?? '') === 'home';
            $slug   = $isHome ? '' : (string) $page['slug'];

            // A home page is reachable at /{locale}; everything else hangs off
            // it. Building both from the same rule keeps this in step with the
            // routes rather than restating them.
            $urls = [];
            foreach ($locales as $locale) {
                $urls[$locale] = rtrim(base_url($locale . ($slug === '' ? '' : '/' . $slug)), '/');
            }

            foreach ($locales as $locale) {
                $entries[] = [
                    'loc'        => $urls[$locale],
                    'alternates' => $urls,
                    'lastmod'    => $this->stamp($page),
                    // The home page is the entry point and is updated most; the
                    // rest sit a step below it. Priority is a hint and engines
                    // mostly ignore it, so this stays simple rather than
                    // inventing a scale nobody reads.
                    'priority'   => $isHome ? '1.0' : '0.8',
                    'changefreq' => $isHome ? 'weekly' : 'monthly',
                ];
            }
        }

        // The section indexes: real addresses, and the entry point to
        // everything below them.
        foreach ([
            'services'    => '0.9',
            'directory'   => '0.8',
            'statistics'  => '0.8',
            'downloads'   => '0.8',
            'hantana'     => '0.8',
            'vacancies'   => '0.8',
            'news'        => '0.8',
            'announcements' => '0.8',
            'discussion'  => '0.6',
            'faqs'        => '0.7',
            'contact'     => '0.8',
            'feedback'    => '0.7',
            'gallery'     => '0.6',
            'videos'      => '0.6',
            'sitemap'     => '0.4',
        ] as $slug => $priority) {
            $entries = array_merge($entries, $this->localized($slug, $locales, null, $priority, 'weekly'));
        }

        // The records. Wrapped, because a sitemap is fetched by a crawler
        // rather than a person: one unreachable table must not take the whole
        // file down and cost the site its indexing.
        foreach ([
            ['services/',   fn () => (new ServiceModel())->live()->findAll(),        '0.7', 'monthly'],
            ['statistics/', fn () => (new StatisticModel())->live(),                 '0.6', 'monthly'],
            ['hantana/',    fn () => (new ProgrammeModel())->calendar(),             '0.6', 'weekly'],
            ['news/',       fn () => (new NewsPostModel())->live()->findAll(200),    '0.6', 'monthly'],
            ['vacancies/',  fn () => (new JobModel())->openJobs(),                   '0.6', 'weekly'],
            ['discussion/', fn () => (new DiscussionTopicModel())->live(),           '0.5', 'weekly'],
        ] as [$prefix, $fetch, $priority, $freq]) {
            try {
                foreach ($fetch() as $row) {
                    $entries = array_merge($entries, $this->localized(
                        $prefix . $row['slug'],
                        $locales,
                        $this->stamp($row),
                        $priority,
                        $freq
                    ));
                }
            } catch (\Throwable $e) {
                log_message('error', 'Sitemap section ' . $prefix . ' skipped: ' . $e->getMessage());
            }
        }

        return $this->response
            ->setHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->setBody($this->renderXml(['entries' => $entries]));
    }

    /**
     * One address, in every language, each entry naming the others.
     *
     * @return list<array>
     */
    private function localized(string $path, array $locales, ?string $lastmod, string $priority, string $changefreq): array
    {
        $urls = [];
        foreach ($locales as $locale) {
            $urls[$locale] = rtrim(base_url($locale . '/' . ltrim($path, '/')), '/');
        }

        $out = [];
        foreach ($locales as $locale) {
            $out[] = [
                'loc'        => $urls[$locale],
                'alternates' => $urls,
                'lastmod'    => $lastmod,
                'priority'   => $priority,
                'changefreq' => $changefreq,
            ];
        }

        return $out;
    }

    /**
     * Render the document with the view debugger switched off.
     *
     * view() wraps each rendered file in an HTML comment naming it whenever the
     * app is in debug mode. In a web page that is invisible and useful; here it
     * lands above the XML declaration, and content before the declaration is not
     * a warning — it is a fatal parse error, so the sitemap would be rejected
     * outright by every consumer on any host running in development.
     */
    private function renderXml(array $data): string
    {
        // The view path is not optional: View's constructor rtrim()s it, so a
        // null there is a TypeError rather than a default.
        $view = new \CodeIgniter\View\View(config(\Config\View::class), APPPATH . 'Views/', null, false);

        return $view->setData($data, 'raw')->render('Modules\Site\Views\sitemap', null, false);
    }

    /** W3C-datetime lastmod, or null when the row has no usable timestamp. */
    private function stamp(array $page): ?string
    {
        $raw = $page['updated_at'] ?? $page['published_at'] ?? $page['created_at'] ?? null;
        if (empty($raw)) {
            return null;
        }
        $time = strtotime((string) $raw);

        return $time === false ? null : date('c', $time);
    }
}
