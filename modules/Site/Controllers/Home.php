<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\BundleModel;
use Modules\Catalog\Models\CourseCategoryModel;
use Modules\Catalog\Models\CourseModel;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Catalog\Models\InstructorModel;
use Modules\Catalog\Models\ReviewModel;
use Modules\Commerce\Services\InventoryService;

/**
 * The home page.
 *
 * Its job is to answer three questions in the first screen — what is taught,
 * when is the next one, and what does it cost — and then to give the two
 * pillars enough room that somebody who came for Photoshop discovers there is
 * an AI track, and the other way round.
 *
 * The section that does the most work is the live table of upcoming dates. It
 * is not decoration: a training school's credibility rests on the reader
 * believing that classes actually run, and eight real dates with real prices
 * says that better than any amount of copy. It is also the section that goes
 * stale fastest, which is why it is a query rather than something somebody has
 * to remember to update.
 *
 * What is deliberately NOT here: invented numbers. The credibility bar carries
 * only figures this code can count from the database — how many courses are
 * published, how many carry a certification alignment, the seat cap the booking
 * system actually enforces. "Learners trained" is left out entirely until there
 * is a booking record to count it from, because a number nobody can evidence is
 * the fastest way to lose the trust the bar exists to build.
 */
class Home extends BaseController
{
    /**
     * The bare root.
     *
     * The site this was forked from opened on a language chooser, because a
     * government portal serving three languages owes its readers a front door
     * that presumes none of them. A commercial site does not: an interstitial
     * between a search result and the page somebody clicked is a bounce. The
     * language is still asked, once, by the first-visit modal — which asks
     * without blocking.
     */
    public function root()
    {
        $supported = config('App')->supportedLocales;

        $stored = (string) ($this->request->getCookie('nl_locale') ?? '');
        $locale = in_array($stored, $supported, true) ? $stored : $this->request->getLocale();

        if (! in_array($locale, $supported, true)) {
            $locale = config('App')->defaultLocale;
        }

        // 302, not 301. Which language a visitor is sent to depends on their
        // browser and can change; a permanently cached redirect would pin every
        // future visitor from that machine to whatever the first one asked for.
        return redirect()->to(base_url($locale))->setStatusCode(302);
    }

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $currency = current_currency();
        $courses  = new CourseModel();

        $upcoming = $this->priceSessions((new CourseSessionModel())->upcoming(8), $currency);

        $pillars = [];
        foreach (['adobe', 'ai'] as $pillar) {
            $pillars[$pillar] = [
                'categories' => (new CourseCategoryModel())->live()
                    ->where('pillar', $pillar)->where('parent_id IS NOT NULL')
                    ->findAll(9),
                'courses' => $this->withCardData($courses->byPillar($pillar, 3), $currency),
                'count'   => $courses->live()->where('courses.pillar', $pillar)->countAllResults(),
            ];
        }

        return view('Modules\Site\Views\home\index', [
            'upcoming'        => $upcoming,
            'pillars'         => $pillars,
            'featured'        => $this->withCardData($courses->featured(6), $currency),
            'bundles'         => (new BundleModel())->ofType('certificate', 3),
            'instructors'     => (new InstructorModel())->live()->findAll(4),
            'reviews'         => (new ReviewModel())->latest(6),
            'posts'           => $this->latestPosts(3),
            'facts'           => $this->facts(),
            'currency'        => $currency,
            'schema'          => Schema::render([Schema::organisation()]),
            'title'           => setting('site_name', '') . ' — ' . lang('Site.home.tagline'),
            'metaDescription' => lang('Site.home.meta'),
            'canonical'       => locale_url(''),
            // The hero is an .on-dark band, so the floating header takes the
            // dark scope until it goes solid. See layouts/main.php.
            'heroDark'        => true,
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Attach the price and the seats left to a list of sessions.
     *
     * Three queries for eight rows rather than sixteen: the seat counts come
     * back in one call and the prices in one more. Asking per row is how a home
     * page quietly becomes the slowest page on a site.
     *
     * @param list<array> $rows
     * @return list<array>
     */
    private function priceSessions(array $rows, string $currency): array
    {
        if ($rows === []) {
            return [];
        }

        $ids   = array_map('intval', array_column($rows, 'id'));
        $seats = (new InventoryService())->seatsLeftFor($ids);

        $prices = array_column(
            db_connect()->table('session_prices')
                ->select('session_id, price_cents')
                ->whereIn('session_id', $ids)->where('currency', $currency)
                ->get()->getResultArray(),
            'price_cents',
            'session_id'
        );

        foreach ($rows as &$row) {
            $row['price_cents'] = isset($prices[$row['id']]) ? (int) $prices[$row['id']] : null;
            $row['seats_left']  = $seats[(int) $row['id']] ?? null;
        }

        return $rows;
    }

    /**
     * The "from" price and next date for a set of course cards.
     *
     * @param list<array> $rows
     * @return list<array>
     */
    private function withCardData(array $rows, string $currency): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));
        $db  = db_connect();

        $from = array_column(
            $db->table('course_sessions cs')
                ->select('cs.course_id, MIN(sp.price_cents) AS from_cents', false)
                ->join('session_prices sp', 'sp.session_id = cs.id')
                ->whereIn('cs.course_id', $ids)->where('sp.currency', $currency)
                ->where('cs.is_private', 0)->whereIn('cs.status', CourseSessionModel::BOOKABLE)
                ->groupBy('cs.course_id')->get()->getResultArray(),
            'from_cents',
            'course_id'
        );

        $next = array_column(
            $db->table('course_sessions')
                ->select('course_id, MIN(start_date) AS next_date', false)
                ->whereIn('course_id', $ids)->where('is_private', 0)
                ->whereIn('status', CourseSessionModel::BOOKABLE)
                ->where('start_date >=', date('Y-m-d'))
                ->groupBy('course_id')->get()->getResultArray(),
            'next_date',
            'course_id'
        );

        foreach ($rows as &$row) {
            $row['from_cents'] = isset($from[$row['id']]) ? (int) $from[$row['id']] : null;
            $row['next_date']  = $next[$row['id']] ?? null;
            $row['currency']   = $currency;
        }

        return $rows;
    }

    /**
     * The latest blog posts, if the News module has any.
     *
     * Wrapped because the home page must render on a site whose editorial
     * calendar has not started yet — an empty section is fine, an exception on
     * the front page is not.
     *
     * @return list<array>
     */
    private function latestPosts(int $limit): array
    {
        try {
            return db_connect()->table('news_posts')
                ->select('id, slug, title, excerpt, image, published_at')
                ->where('status', 'published')
                ->where('published_at <=', date('Y-m-d H:i:s'))
                ->orderBy('published_at', 'DESC')
                ->limit($limit)->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('warning', 'Home page could not read news_posts: {msg}', ['msg' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * The credibility bar, derived rather than typed.
     *
     * Every figure is counted from the database at render time, so it cannot
     * drift out of date and cannot be optimistic. An entry whose value cannot
     * be derived is omitted rather than shown as a placeholder — a bar with one
     * fewer item is honest, a bar with "{learners_trained}" in it is broken,
     * and a bar with an invented number is worse than either.
     *
     * @return list<array{label:string, value:string}>
     */
    private function facts(): array
    {
        $db = db_connect();

        $published = $db->table('courses')
            ->where('status', 'published')->where('deleted_at IS NULL')->countAllResults();

        // A course "prepares for" an exam only if somebody wrote which exam.
        // The column is a JSON locale map, so an unset one is null, '' or the
        // empty map — all three have to be excluded or the count flatters.
        $aligned = $db->table('courses')
            ->where('status', 'published')->where('deleted_at IS NULL')
            ->where('certification_alignment IS NOT NULL')
            ->where('certification_alignment !=', '')
            ->where('certification_alignment !=', '[]')
            ->where('certification_alignment !=', '{}')
            ->countAllResults();

        $cap = (int) ($db->table('course_sessions')
            ->selectMax('seats_total')
            ->where('seats_total >', 0)
            ->get()->getRowArray()['seats_total'] ?? 0);

        $modes = array_column(
            $db->table('course_sessions')->select('mode')->distinct()
                ->whereIn('status', CourseSessionModel::BOOKABLE)
                ->where('is_private', 0)
                ->get()->getResultArray(),
            'mode'
        );

        $facts = [];
        if ($published > 0) {
            $facts[] = ['label' => lang('Site.home.fact_courses'), 'value' => (string) $published];
        }
        if ($aligned > 0) {
            $facts[] = ['label' => lang('Site.home.fact_aligned'), 'value' => (string) $aligned];
        }
        if ($cap > 0) {
            $facts[] = ['label' => lang('Site.home.fact_class_size'), 'value' => (string) $cap];
        }
        if ($modes !== []) {
            $facts[] = [
                'label' => lang('Site.home.fact_delivery'),
                'value' => implode(' · ', array_filter(array_map('mode_label', $modes))),
            ];
        }

        return $facts;
    }
}
