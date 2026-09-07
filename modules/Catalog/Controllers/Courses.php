<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\CourseCategoryModel;
use Modules\Catalog\Models\CourseModel;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Catalog\Models\ReviewModel;
use Modules\Commerce\Libraries\CartContext;
use Modules\Commerce\Services\InventoryService;
use Modules\Commerce\Services\PricingService;

/**
 * The catalogue, and the course page.
 *
 * The course page is the money page: it is where organic search lands, where
 * the decision is made, and where every other page on the site is trying to
 * send people. Three things on it do the work, and each is here rather than in
 * the view because each depends on who is asking:
 *
 *   1. **The dates table.** Real dates, with a real price and a real place, on
 *      the page itself. A course page without dates is a brochure.
 *   2. **The price, in the visitor's own currency**, resolved once and frozen
 *      onto their cart.
 *   3. **The mode switch** — live online, in person, self-paced — changing the
 *      price and the call to action together. The blueprint calls this the
 *      single highest-leverage element on the site and it is hard to disagree:
 *      it is the difference between "we might do that" and "here is what that
 *      costs".
 */
class Courses extends BaseController
{
    private const PER_PAGE = 12;

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce']);

        $courses    = new CourseModel();
        $categories = new CourseCategoryModel();
        $pricing    = new PricingService();
        $currency   = current_currency();

        $filters = $this->readFilters();
        $page    = max(1, (int) $this->request->getGet('page'));

        $result = $courses->catalogue($filters, self::PER_PAGE, $page);

        return view('Modules\Catalog\Views\courses\index', [
            'courses'         => $this->decorate($result['rows'], $pricing, $currency),
            'total'           => $result['total'],
            'page'            => $page,
            'perPage'         => self::PER_PAGE,
            'tree'            => $categories->tree(),
            'filters'         => $filters,
            'currency'        => $currency,
            'category'        => null,
            'crumbs'          => [['label' => lang('Catalog.courses.title')]],
            'title'           => lang('Catalog.courses.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.courses.meta'),
        ]);
    }

    /**
     * A category page.
     *
     * Category slugs are globally unique, so one segment addresses any depth of
     * the tree — /courses/adobe-creative-cloud and /courses/photoshop are both
     * this route, and the breadcrumb reconstructs the path.
     */
    public function category(?string $locale = null, ?string $slug = null)
    {
        helper(['norlanka', 'catalog', 'commerce']);

        $categories = new CourseCategoryModel();
        $category   = $categories->findLive((string) $slug);
        if ($category === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $pricing  = new PricingService();
        $currency = current_currency();
        $page     = max(1, (int) $this->request->getGet('page'));

        $filters = $this->readFilters() + [];
        // The whole subtree, not just this node: a course sits in a leaf, so
        // filtering by "Adobe Creative Cloud" has to collect everything under
        // it or the branch pages all come back empty.
        $filters['category_ids'] = $categories->descendantIds((int) $category['id']);

        $result = (new CourseModel())->catalogue($filters, self::PER_PAGE, $page);

        $trail  = $categories->ancestors($category);
        $crumbs = [['label' => lang('Catalog.courses.title'), 'url' => locale_url('courses')]];
        foreach ($trail as $i => $node) {
            $crumbs[] = [
                'label' => t_field($node['name']),
                'url'   => $i === count($trail) - 1 ? null : locale_url('courses/' . $node['slug']),
            ];
        }

        return view('Modules\Catalog\Views\courses\index', [
            'courses'         => $this->decorate($result['rows'], $pricing, $currency),
            'total'           => $result['total'],
            'page'            => $page,
            'perPage'         => self::PER_PAGE,
            'tree'            => $categories->tree(),
            'filters'         => $filters,
            'currency'        => $currency,
            'category'        => $category,
            'children'        => $categories->live()->where('parent_id', (int) $category['id'])->findAll(),
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => t_field($category['seo_title']) ?: (t_field($category['name']) . ' ' . lang('Catalog.courses.training') . ' — ' . setting('site_name', '')),
            'metaDescription' => t_field($category['seo_description']) ?: t_field($category['summary']),
            'metaKeywords'    => (string) $category['seo_keywords'],
        ]);
    }

    /** The money page. */
    public function show(?string $locale = null, ?string $slug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $courses = new CourseModel();
        $course  = $courses->findLive((string) $slug);
        if ($course === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $detail   = $courses->detail((int) $course['id']);
        $pricing  = new PricingService();
        $currency = current_currency();

        $sessionModel = new CourseSessionModel();
        $inventory    = new InventoryService();
        $cart         = CartContext::existing();

        // Dates grouped by mode, because the booking panel switches between
        // them and each tab needs its own list and its own price.
        $byMode  = [];
        $prices  = [];
        $allSessions = $sessionModel->forCourse((int) $course['id'], null, 40);

        $seatsLeft = $inventory->seatsLeftFor(
            array_map('intval', array_column($allSessions, 'id')),
            $cart ? (int) $cart['id'] : null
        );

        foreach ($allSessions as $session) {
            $price = $pricing->sessionPrice((int) $session['id'], $currency);
            // A session with no price in this visitor's currency has not been
            // priced for their market. It is left out rather than converted,
            // and the panel says which currencies the course is sold in.
            if ($price === null) {
                continue;
            }

            $session['price_cents']      = $price['price_cents'];
            $session['compare_at_cents'] = $price['compare_at_cents'];
            $session['seats_left']       = $seatsLeft[(int) $session['id']] ?? null;
            $session['course_slug']      = $course['slug'];

            $byMode[$session['mode']][] = $session;

            if (! isset($prices[$session['mode']]) || $price['price_cents'] < $prices[$session['mode']]['price_cents']) {
                $prices[$session['mode']] = $price;
            }
        }

        // The tabs come from what the course *offers*, not from what happens to
        // be scheduled. A course available in person with no classroom date this
        // month is still an in-person course, and hiding the tab loses the
        // enquiry that would have created the date.
        $modes = $detail['modes'] !== [] ? $detail['modes'] : [$course['default_mode']];
        $modes = array_values(array_intersect(CourseSessionModel::MODES, $modes));

        $reviews = (new ReviewModel())->forCourse((int) $course['id'], 12);

        $categories = new CourseCategoryModel();
        $crumbs     = [['label' => lang('Catalog.courses.title'), 'url' => locale_url('courses')]];
        if (! empty($course['category_id']) && ($category = $categories->find((int) $course['category_id'])) !== null) {
            foreach ($categories->ancestors($category) as $node) {
                $crumbs[] = ['label' => t_field($node['name']), 'url' => locale_url('courses/' . $node['slug'])];
            }
        }
        $crumbs[] = ['label' => t_field($course['title'])];

        return view('Modules\Catalog\Views\courses\show', [
            'course'          => $course,
            'detail'          => $detail,
            'byMode'          => $byMode,
            'prices'          => $prices,
            'modes'           => $modes,
            'currency'        => $currency,
            'reviews'         => $reviews,
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([
                Schema::organisation(),
                Schema::course($course, array_merge(...array_values($byMode ?: [[]])), $currency),
                Schema::faqPage($detail['faqs']),
                Schema::breadcrumbs($crumbs),
            ]),
            'title'           => t_field($course['seo_title']) ?: (t_field($course['title']) . ' — ' . setting('site_name', '')),
            'metaDescription' => t_field($course['seo_description']) ?: t_field($course['summary']),
            'metaKeywords'    => (string) $course['seo_keywords'],
            'ogImage'         => $course['hero_image'] ? media_src($course['hero_image']) : null,
            'lastUpdated'     => $course['updated_at'],
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * The facets, validated.
     *
     * An unknown value is a 404 rather than a silently ignored parameter:
     * /courses?level=nonsense returning the whole catalogue looks exactly like
     * a working filter that matched everything.
     */
    private function readFilters(): array
    {
        $filters = [];

        $pillar = trim((string) $this->request->getGet('pillar'));
        if ($pillar !== '') {
            if (! in_array($pillar, ['adobe', 'ai', 'design'], true)) {
                throw PageNotFoundException::forPageNotFound();
            }
            $filters['pillar'] = $pillar;
        }

        $level = trim((string) $this->request->getGet('level'));
        if ($level !== '') {
            if (! in_array($level, ['1', '2', '3'], true)) {
                throw PageNotFoundException::forPageNotFound();
            }
            $filters['level'] = (int) $level;
        }

        $mode = trim((string) $this->request->getGet('mode'));
        if ($mode !== '') {
            if (! in_array($mode, CourseSessionModel::MODES, true)) {
                throw PageNotFoundException::forPageNotFound();
            }
            $filters['mode'] = $mode;
        }

        $q = trim((string) $this->request->getGet('q'));
        if ($q !== '') {
            $filters['q'] = mb_substr($q, 0, 100);
        }

        return $filters;
    }

    /**
     * Add the "from" price and the next date to each card.
     *
     * Done here rather than in the view so a catalogue of twelve cards is a
     * handful of queries rather than twenty-four — the classic listing-page
     * problem, and the reason a category page gets slower as the school grows.
     *
     * @param list<array> $rows
     * @return list<array>
     */
    private function decorate(array $rows, PricingService $pricing, string $currency): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));
        $db  = db_connect();

        // Cheapest published price per course, in one query.
        $priceRows = $db->table('course_sessions cs')
            ->select('cs.course_id, MIN(sp.price_cents) AS from_cents', false)
            ->join('session_prices sp', 'sp.session_id = cs.id')
            ->whereIn('cs.course_id', $ids)
            ->where('sp.currency', $currency)
            ->where('cs.is_private', 0)
            ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
            ->groupBy('cs.course_id')
            ->get()->getResultArray();
        $from = array_column($priceRows, 'from_cents', 'course_id');

        // Next date per course, in another.
        $dateRows = $db->table('course_sessions')
            ->select('course_id, MIN(start_date) AS next_date', false)
            ->whereIn('course_id', $ids)
            ->where('is_private', 0)
            ->whereIn('status', CourseSessionModel::BOOKABLE)
            ->where('start_date >=', date('Y-m-d'))
            ->groupBy('course_id')
            ->get()->getResultArray();
        $next = array_column($dateRows, 'next_date', 'course_id');

        foreach ($rows as &$row) {
            $row['from_cents'] = isset($from[$row['id']]) ? (int) $from[$row['id']] : null;
            $row['next_date']  = $next[$row['id']] ?? null;
            $row['currency']   = $currency;
        }

        return $rows;
    }
}
