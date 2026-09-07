<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\BundleModel;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Commerce\Services\PricingService;

/**
 * Certificate programmes and bootcamps — several courses sold as one thing.
 *
 * Four decisions are encoded here, and each of them is the reason a line of
 * this file looks the way it does rather than the shorter way.
 *
 * **Two front doors, one detail page.** /certificates and /bootcamps differ
 * only by the `type` column: a bootcamp is an intensive block over consecutive
 * days, a certificate programme is the same idea spread over months with a
 * through-line. They are listed apart because a buyer arrives wanting one or
 * the other, and shown on one detail template because maintaining two that
 * differ by a heading is how two pages drift into disagreeing about a price.
 *
 * **A programme has exactly one address.** Both routes point at `show()`, so
 * /certificates/{a-bootcamp} resolves. It does not stay there: the canonical
 * section comes from the row's own `type` and anything else is a permanent
 * redirect, because the same programme reachable at two URLs is duplicate
 * content that splits its own ranking.
 *
 * **The saving is the product.** A bundle exists to be cheaper than its parts,
 * and `BundleModel::saving()` is the only thing that knows by how much. It
 * returns null when any course in the programme has no published price in this
 * visitor's currency — the sum is then unknown, and an unknown saving is shown
 * as nothing at all rather than as zero.
 *
 * **A bundle holds no seats.** Buying one enrols somebody on a set of courses,
 * not on a set of dates; the dates are chosen afterwards from the account area,
 * one course at a time. Nothing here goes near InventoryService, and the page
 * says so beside the button rather than leaving a buyer to work out what they
 * have just been sold.
 */
class Bundles extends BaseController
{
    /**
     * Which section of the site each bundle type lives under.
     *
     * The map runs type → URL segment and is the single source of both the
     * canonical address and the breadcrumb, so a new type added to the schema
     * shows up here as a missing key rather than as a page quietly filed under
     * the wrong heading.
     */
    private const SECTIONS = [
        'certificate' => 'certificates',
        'bootcamp'    => 'bootcamps',
    ];

    // ── The two listings ────────────────────────────────────────────────────

    public function certificates(?string $locale = null)
    {
        return $this->listing('certificate');
    }

    public function bootcamps(?string $locale = null)
    {
        return $this->listing('bootcamp');
    }

    // ── One programme ───────────────────────────────────────────────────────

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $bundles = new BundleModel();
        $bundle  = $bundles->findLive((string) $slug);
        if ($bundle === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $section = $this->sectionFor($bundle);
        $url     = locale_url($section . '/' . $bundle['slug']);

        // Which of the two prefixes the visitor actually used. getPath() is
        // relative to baseURL, so segment 0 is the locale and segment 1 is the
        // section, whatever subfolder the site is installed in — which is not
        // true of the raw URI, and is why this is not read off getUri().
        $segments  = explode('/', trim($this->request->getPath(), '/'));
        $requested = $segments[1] ?? '';

        if ($requested !== $section) {
            // 301 rather than rendering here with a canonical tag: the wrong
            // prefix is a guess somebody typed or a stale link, and the cheapest
            // way to stop it accumulating inbound links is to never serve a
            // page at it.
            return redirect()->to($url, 301);
        }

        $id       = (int) $bundle['id'];
        $currency = current_currency();
        $pricing  = new PricingService();

        $courses = $this->decorate($bundles->courses($id), $currency);
        $price   = $pricing->bundlePrice($id, $currency);

        // Null when a course in the programme has no price in this currency;
        // zero when the programme is priced at or above the sum of its parts,
        // which happens while a price list is half-entered. Neither is worth a
        // badge, and "save nothing" beside a buy button is actively repellent.
        $saving = $bundles->saving($id, $currency);
        if ($saving !== null && $saving <= 0) {
            $saving = null;
        }

        $detail = $this->detail($id, $courses);

        $crumbs = [
            ['label' => $this->sectionLabel($section), 'url' => locale_url($section)],
            ['label' => t_field($bundle['title'])],
        ];

        return view('Modules\Catalog\Views\bundles\show', [
            'bundle'          => $bundle,
            'section'         => $section,
            'courses'         => $courses,
            'detail'          => $detail,
            'price'           => $price,
            'saving'          => $saving,
            'hours'           => $this->taughtHours($courses),
            'currency'        => $currency,
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([
                Schema::organisation(),
                $this->programmeGraph($bundle, $courses, $price, $url, $currency),
                Schema::faqPage($detail['faqs']),
                Schema::breadcrumbs($crumbs),
            ]),
            'canonical'       => $url,
            'title'           => t_field($bundle['seo_title']) ?: (t_field($bundle['title']) . ' — ' . setting('site_name', '')),
            'metaDescription' => t_field($bundle['seo_description']) ?: t_field($bundle['summary']),
            'metaKeywords'    => (string) $bundle['seo_keywords'],
            'ogImage'         => $bundle['hero_image'] ? media_src($bundle['hero_image']) : null,
            'lastUpdated'     => $bundle['updated_at'],
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * One listing, for either type.
     *
     * Both sections are short by nature — a school publishes a handful of
     * programmes, not a catalogue of them — so there is no pagination and no
     * facet rail here. If the list ever grows past a screenful, the thing to
     * add is pagination, not a filter: nobody browses seven items by facet.
     */
    private function listing(string $type)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $bundles  = new BundleModel();
        $currency = current_currency();
        $pricing  = new PricingService();
        $section  = self::SECTIONS[$type];

        $rows   = $bundles->ofType($type);
        $rollUp = $this->rollUp(array_map('intval', array_column($rows, 'id')));

        $cards = [];
        foreach ($rows as $row) {
            $id = (int) $row['id'];

            // `saving()` costs one query per course in the programme, which on
            // a paginated catalogue would be indefensible and on a page of
            // seven rows is simply the price of the number being right. It is
            // deliberately not reimplemented set-wise here: a saving printed on
            // a card and a saving printed on the page that card links to have
            // to be the same figure, and two implementations of one money
            // calculation eventually are not.
            $saving = $bundles->saving($id, $currency);

            $cards[] = $row + [
                'url'          => locale_url($section . '/' . $row['slug']),
                'price'        => $pricing->bundlePrice($id, $currency),
                'saving'       => $saving !== null && $saving > 0 ? $saving : null,
                'course_count' => (int) ($rollUp[$id]['course_count'] ?? 0),
                'hours'        => (int) ($rollUp[$id]['hours'] ?? 0),
            ];
        }

        $other = $type === 'certificate' ? 'bootcamps' : 'certificates';
        $key   = $type === 'certificate' ? 'certificates' : 'bootcamps';

        // Built once and used for both the rendered trail and the structured
        // one: `BreadcrumbList` that disagrees with the breadcrumb on the page
        // is the sort of mismatch a search engine reads as a page trying it on.
        $crumbs = [['label' => lang('Catalog.bundles.' . $key . '_title')]];

        return view('Modules\Catalog\Views\bundles\index', [
            'bundles'         => $cards,
            'type'            => $type,
            'section'         => $section,
            'other'           => $other,
            'currency'        => $currency,
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([
                Schema::organisation(),
                Schema::breadcrumbs($crumbs),
            ]),
            'title'           => lang('Catalog.bundles.' . $key . '_title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.bundles.' . $key . '_meta'),
        ]);
    }

    /**
     * Course count and taught hours per bundle, for the listing cards.
     *
     * One grouped query for the whole page rather than a `courses()` call per
     * card: this is the same listing-page trap `Courses::decorate()` avoids,
     * and it is worth avoiding even on a short page because it is the shape
     * somebody copies onto a long one.
     *
     * @param list<int> $bundleIds
     * @return array<int, array{course_count:int, hours:int}>
     */
    private function rollUp(array $bundleIds): array
    {
        if ($bundleIds === []) {
            return [];
        }

        $rows = db_connect()->table('bundle_items bi')
            ->select('bi.bundle_id, COUNT(*) AS course_count, SUM(c.duration_hours) AS hours', false)
            ->join('courses c', 'c.id = bi.course_id')
            ->whereIn('bi.bundle_id', $bundleIds)
            ->where('c.deleted_at IS NULL')
            ->groupBy('bi.bundle_id')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['bundle_id']] = [
                'course_count' => (int) $row['course_count'],
                'hours'        => (int) $row['hours'],
            ];
        }

        return $out;
    }

    /**
     * Add each course's cheapest published price to the programme's course list.
     *
     * The filter is deliberately identical to the one inside
     * `BundleModel::saving()` — bookable statuses, no exclusion of private
     * dates — because these are the numbers the saving is measured against. A
     * page whose per-course prices do not add up to the "bought separately"
     * figure beside them invites exactly the arithmetic a buyer should never be
     * moved to do.
     *
     * @param list<array> $courses
     * @return list<array>
     */
    private function decorate(array $courses, string $currency): array
    {
        if ($courses === []) {
            return [];
        }

        $ids = array_map('intval', array_column($courses, 'id'));

        $rows = db_connect()->table('course_sessions cs')
            ->select('cs.course_id, MIN(sp.price_cents) AS from_cents', false)
            ->join('session_prices sp', 'sp.session_id = cs.id')
            ->whereIn('cs.course_id', $ids)
            ->where('sp.currency', $currency)
            ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
            ->groupBy('cs.course_id')
            ->get()->getResultArray();

        $from = array_column($rows, 'from_cents', 'course_id');

        foreach ($courses as &$course) {
            $course['from_cents'] = isset($from[$course['id']]) ? (int) $from[$course['id']] : null;
            $course['currency']   = $currency;
        }

        return $courses;
    }

    /**
     * The programme's own outcomes, inclusions and questions.
     *
     * The seed data carries all three per bundle, but the schema has nowhere to
     * put them: there is no `bundle_outcomes`, `bundle_includes` or
     * `bundle_faqs` table, so the seeder drops those keys on the floor. Until
     * they exist this reads them when they do and falls back meanwhile, which
     * means the page needs no second edit once the migration lands.
     *
     * Outcomes and inclusions fall back to the union of the constituent
     * courses' own, deduplicated on the resolved text: a programme's outcomes
     * genuinely are its courses' outcomes, and "recordings for twelve months"
     * said six times is one fact, not six.
     *
     * FAQs have no fallback. A course FAQ answers a question about that course
     * — its software, its licence, its exam — and reprinting one under a
     * nine-course programme answers a buyer's question with a sentence about
     * something they were not asking about. Silence is the honest option.
     *
     * @param list<array> $courses
     * @return array{outcomes:list<array>, includes:list<array>, faqs:list<array>}
     */
    private function detail(int $bundleId, array $courses): array
    {
        $courseIds = array_map('intval', array_column($courses, 'id'));

        return [
            'outcomes' => $this->optional('bundle_outcomes', $bundleId)
                ?: $this->merged('course_outcomes', $courseIds),
            'includes' => $this->optional('bundle_includes', $bundleId)
                ?: $this->merged('course_includes', $courseIds),
            'faqs'     => $this->optional('bundle_faqs', $bundleId),
        ];
    }

    /**
     * Rows from a bundle-level table that may not have been migrated yet.
     *
     * `tableExists()` reads the connection's cached table list, so the cost is
     * one metadata query for the whole request rather than one per call.
     *
     * @return list<array>
     */
    private function optional(string $table, int $bundleId): array
    {
        $db = db_connect();

        if (! $db->tableExists($table)) {
            return [];
        }

        return $db->table($table)
            ->where('bundle_id', $bundleId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * One list, gathered from every course in the programme and deduplicated.
     *
     * Ordered by the programme's own course order first, so the list reads in
     * the sequence the courses are taken rather than in primary-key order, and
     * the first wording of a repeated line is the one that survives.
     *
     * @param list<int> $courseIds in bundle order
     * @return list<array>
     */
    private function merged(string $table, array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        helper('norlanka');

        $rows = db_connect()->table($table)
            ->whereIn('course_id', $courseIds)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        // Position in the bundle, not the course id: sorting by course_id would
        // put "advanced" before "introduction" whenever the advanced course was
        // entered first.
        $position = array_flip($courseIds);
        usort($rows, static fn (array $a, array $b): int => [
            $position[(int) $a['course_id']] ?? PHP_INT_MAX, (int) $a['sort_order'], (int) $a['id'],
        ] <=> [
            $position[(int) $b['course_id']] ?? PHP_INT_MAX, (int) $b['sort_order'], (int) $b['id'],
        ]);

        // Deduplicated on what the reader will actually see, not on the stored
        // JSON: two rows carrying the same English sentence with different
        // Sinhala are one line on this page in either language.
        $seen = [];
        $out  = [];
        foreach ($rows as $row) {
            $text = trim(t_field($row['text']));
            if ($text === '' || isset($seen[$text])) {
                continue;
            }
            $seen[$text] = true;
            $out[]       = $row;
        }

        return $out;
    }

    /**
     * The programme as a `Course` whose `hasPart` entries are its courses.
     *
     * Built here rather than in Schema because it is the only graph on the site
     * with this shape, and because what goes in it depends on what the page
     * managed to show: no `hasCourseInstance`, since a bundle has no dates of
     * its own and inventing one would be a `CourseInstance` for a class that
     * does not exist.
     *
     * @param list<array> $courses
     * @param array{price_cents:int, compare_at_cents:?int}|null $price
     */
    private function programmeGraph(array $bundle, array $courses, ?array $price, string $url, string $currency): array
    {
        $organisation = ['@id' => rtrim(base_url(), '/') . '/#organisation'];

        $parts = [];
        foreach ($courses as $course) {
            // A `hasPart` entry is a claim that there is a course at that
            // address. An unpublished one 404s, so it is listed on the page —
            // it is part of what is being sold — but not asserted to a crawler.
            if ((string) $course['status'] !== 'published') {
                continue;
            }

            $parts[] = array_filter([
                '@type'       => 'Course',
                'name'        => t_field($course['title']),
                'description' => t_field($course['summary']) ?: null,
                'url'         => course_url($course['slug']),
                'provider'    => $organisation,
            ]);
        }

        $graph = [
            '@type'               => 'Course',
            '@id'                 => $url . '#programme',
            'name'                => t_field($bundle['title']),
            'description'         => t_field($bundle['summary']) ?: trim(strip_tags(t_field($bundle['description']))),
            'url'                 => $url,
            'provider'            => $organisation,
            'isAccessibleForFree' => false,
        ];

        if ($bundle['hero_image']) {
            $graph['image'] = rtrim(base_url(), '/') . media_src($bundle['hero_image']);
        }

        // ISO 8601. The sum of the taught hours of its courses, which is a
        // figure that exists rather than an estimate of elapsed weeks.
        $hours = $this->taughtHours($courses);
        if ($hours > 0) {
            $graph['timeRequired'] = 'PT' . $hours . 'H';
        }

        if ($parts !== []) {
            $graph['hasPart'] = $parts;
        }

        // Only the price the visitor is being shown, in the currency they are
        // being shown it in. With no published price the page says so in words
        // and the graph carries no offer, rather than one in a currency nobody
        // asked for.
        if ($price !== null) {
            $graph['offers'] = [
                '@type'         => 'Offer',
                'price'         => number_format($price['price_cents'] / 100, 2, '.', ''),
                'priceCurrency' => $currency,
                'category'      => 'Paid',
                'url'           => $url,
                // A programme cannot sell out: it holds no seats. The dates it
                // is redeemed against can, and each of those says so on its own
                // page.
                'availability'  => 'https://schema.org/InStock',
            ];
        }

        return $graph;
    }

    /** @param list<array> $courses */
    private function taughtHours(array $courses): int
    {
        $hours = 0;
        foreach ($courses as $course) {
            $hours += (int) $course['duration_hours'];
        }

        return $hours;
    }

    /**
     * The section a programme belongs under.
     *
     * An unrecognised type falls to the certificate side rather than 404ing: a
     * row whose type somebody mistyped in the admin is a page that should still
     * be reachable, and the wrong heading is a smaller failure than a
     * published programme that cannot be opened.
     */
    private function sectionFor(array $bundle): string
    {
        return self::SECTIONS[(string) $bundle['type']] ?? 'certificates';
    }

    private function sectionLabel(string $section): string
    {
        return $section === 'bootcamps'
            ? lang('Catalog.bundles.bootcamps_title')
            : lang('Catalog.bundles.certificates_title');
    }
}
