<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\CourseCategoryModel;
use Modules\Catalog\Models\CourseModel;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Commerce\Libraries\CartContext;
use Modules\Commerce\Services\InventoryService;
use Modules\Commerce\Services\PricingService;

/**
 * The two pillar hubs, and the Adobe certification hub beneath one of them.
 *
 * /adobe and /ai are the two addresses the whole content plan links back to:
 * every blog post, every resource, every category page and the home page point
 * at one of them, so between them they collect most of the site's internal link
 * equity and most of its "what do you actually teach" traffic. That is why they
 * are built here rather than left to the CMS. A page an editor can empty by
 * accident is not a page to hang the site's internal linking on, and a hub that
 * is only a list of links passes its authority straight through without ever
 * having earned a visitor's attention.
 *
 * So both hubs are assembled from the catalogue itself: the branch of the
 * taxonomy, the courses in it, the programmes that draw on it, and the real
 * dates coming up in it. Every number on them is counted at render time and
 * none of them is typed. A hub whose "40 courses" was written by hand in 2026
 * is wrong by the following spring and nobody notices, because nothing breaks.
 *
 * ── The certification hub ────────────────────────────────────────────────────
 *
 * /adobe/certification is the most legally careful page on this site, and the
 * three facts it turns on are worth stating here rather than only in the copy:
 *
 *   Adobe writes the exam objectives. **Certiport** administers the exam.
 *   **Adobe** awards the credential. MyLearnPlus does none of those three.
 *
 * Everything the page says is bounded by that. It maps each exam to the courses
 * whose `certification_alignment` names it, it explains where the exam is
 * actually booked and sat, and it never suggests the school issues the
 * credential or that any course can guarantee a pass. The disclaimer is the
 * first thing under the heading rather than a line in the footer, because a
 * disclaimer somebody has to scroll past the buy button to reach is a
 * disclaimer written for the lawyer rather than for the buyer.
 */
class Pillars extends BaseController
{
    /** Courses in the "where people start" row. */
    private const FEATURED = 6;

    /** Dates shown on a hub before it defers to the full schedule. */
    private const DATES = 6;

    /**
     * Bundle type → the URL segment its detail page canonically lives at.
     *
     * Deliberately a second copy of the map `Bundles` keeps privately rather
     * than a shared constant: a programme reached at the wrong prefix is
     * permanently redirected by that controller, so a link built here with the
     * wrong segment costs a visitor a redirect and costs the site a diluted
     * URL. Two entries are cheap to keep in step; a public constant on another
     * controller is a coupling that outlives the reason for it.
     */
    private const SECTIONS = [
        'certificate' => 'certificates',
        'bootcamp'    => 'bootcamps',
    ];

    /**
     * The ten Adobe Certified Professional exams, and the string that
     * identifies each in a course's `certification_alignment`.
     *
     * Two of the match terms carry the "Adobe" prefix and the rest do not, and
     * that is not tidiness. **Animate** without it matches "animate",
     * "animated" and "animation", which is ordinary prose in a motion course's
     * alignment sentence and would file that course under an exam it has
     * nothing to do with. **Express** without it matches "express" in any
     * sentence at all. The other eight are product names that appear nowhere
     * else in English.
     *
     * `Acrobat` rather than `Acrobat Pro` because both spellings turn up, and
     * nothing else on the site is called Acrobat.
     */
    private const EXAMS = [
        ['app' => 'Adobe Photoshop',     'match' => 'Photoshop'],
        ['app' => 'Adobe Illustrator',   'match' => 'Illustrator'],
        ['app' => 'Adobe InDesign',      'match' => 'InDesign'],
        ['app' => 'Adobe Premiere Pro',  'match' => 'Premiere Pro'],
        ['app' => 'Adobe After Effects', 'match' => 'After Effects'],
        ['app' => 'Adobe Animate',       'match' => 'Adobe Animate'],
        ['app' => 'Adobe Dreamweaver',   'match' => 'Dreamweaver'],
        ['app' => 'Adobe Acrobat Pro',   'match' => 'Acrobat'],
        ['app' => 'Adobe Express',       'match' => 'Adobe Express'],
        ['app' => 'Adobe Firefly',       'match' => 'Firefly'],
    ];

    /**
     * The four Specialty Credentials, by name only.
     *
     * No pairings are listed, and that is the point. Adobe decides which two
     * professional certifications earn each credential and changes the
     * combinations from time to time; printing last year's pairing next to a
     * course price is how somebody buys two vouchers for a credential they
     * will not be awarded. The page names the four, states the rule, and sends
     * the reader to Adobe for the current combinations.
     */
    private const SPECIALTY = ['Visual Design', 'Video Design', 'Web Design', 'Marketing Design'];

    // ── The two hubs ────────────────────────────────────────────────────────

    public function adobe(?string $locale = null)
    {
        return $this->pillar('adobe');
    }

    public function ai(?string $locale = null)
    {
        return $this->pillar('ai');
    }

    // ── The certification hub ───────────────────────────────────────────────

    public function certification(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $currency = current_currency();
        $exams    = $this->examMap();

        // Whether the exam grid has anything in it at all. A site whose courses
        // have not yet had their alignment written should say so once, at the
        // top of the grid, rather than print ten identical "no course yet"
        // cards and look broken.
        $aligned = 0;
        foreach ($exams as $exam) {
            $aligned += count($exam['courses']);
        }

        // Adobe's own certification pages, if somebody has configured the
        // address. Not hard-coded: an external URL baked into a template is one
        // nobody can fix without a deployment on the day Adobe moves it, and a
        // link to the wrong page is worse on this page than on any other.
        $adobeUrl = trim((string) setting('adobe_certification_url', '', 'catalog'));
        if (! str_starts_with($adobeUrl, 'https://')) {
            $adobeUrl = '';
        }

        // Built once and handed to both the rendered accordion and the
        // FAQPage graph, so the structured data cannot describe questions the
        // page does not ask — the mismatch a search engine reads as a page
        // trying it on.
        $faqs = $this->faqs();

        $crumbs = [
            ['label' => lang('Catalog.pillars.adobe_title'), 'url' => locale_url('adobe')],
            ['label' => lang('Catalog.pillars.cert_title')],
        ];

        return view('Modules\Catalog\Views\pillars\certification', [
            'exams'           => $exams,
            'aligned'         => $aligned,
            'specialty'       => self::SPECIALTY,
            'programmes'      => $this->programmes('adobe', $currency),
            'faqs'            => $faqs,
            'adobeUrl'        => $adobeUrl,
            'catalogueUrl'    => locale_url('courses') . '?pillar=adobe',
            'currency'        => $currency,
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([
                Schema::organisation(),
                Schema::faqPage($faqs),
                Schema::breadcrumbs($crumbs),
            ]),
            'title'           => lang('Catalog.pillars.cert_title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.pillars.cert_meta'),
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * One hub, for either pillar.
     *
     * `adobe` and `ai` differ in their copy and in nothing else, so they render
     * one view. Splitting them would mean maintaining two category grids, two
     * empty states and two dates tables that agree today and disagree in six
     * months, which is exactly what happened to the pair of listing pages this
     * site replaced.
     */
    private function pillar(string $pillar)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $currency = current_currency();
        $pricing  = new PricingService();

        // Every published course in the track, once. The category grid, the
        // counts and the featured row are three views onto this one list;
        // asking the database again per branch is the listing-page trap
        // `Courses::decorate()` exists to avoid, and a hub page is where it
        // does the most damage because the page grows a query every time the
        // taxonomy grows a node.
        $all = (new CourseModel())->live()
            ->where('courses.pillar', $pillar)
            ->orderBy('courses.level', 'ASC')
            ->orderBy('courses.id', 'ASC')
            ->findAll();

        $byCategory = [];
        foreach ($all as $course) {
            $byCategory[(int) $course['category_id']][] = $course;
        }

        $tree = $this->withCounts((new CourseCategoryModel())->tree($pillar), $byCategory);

        // What the school has chosen to lead with, or — while nothing has been
        // chosen — the beginners' courses, which is the same order the
        // catalogue itself presents and a better answer than an empty section.
        $featured = array_values(array_filter($all, static fn (array $c): bool => (int) $c['is_featured'] === 1));
        if ($featured === []) {
            $featured = $all;
        }

        $schedule = (new CourseSessionModel())->schedule(['pillar' => $pillar], self::DATES, 1);

        return view('Modules\Catalog\Views\pillars\pillar', [
            'pillar'          => $pillar,
            'tree'            => $tree,
            'featured'        => $this->decorateCourses(array_slice($featured, 0, self::FEATURED), $currency),
            'programmes'      => $this->programmes($pillar, $currency),
            'dates'           => $this->decorateSessions($schedule['rows'], $pricing, $currency),
            'dateTotal'       => $schedule['total'],
            'facts'           => $this->facts($pillar, count($all), $schedule['total']),
            // The one route this whole page is trying to send people down. Built
            // here rather than in the view because it is a route, and because
            // the facet name has to match what `Courses::readFilters()` accepts
            // or the link 404s.
            'catalogueUrl'    => locale_url('courses') . '?pillar=' . $pillar,
            'scheduleUrl'     => locale_url('schedule') . '?pillar=' . $pillar,
            'currency'        => $currency,
            'crumbs'          => [['label' => lang('Catalog.pillars.' . $pillar . '_title')]],
            'schema'          => Schema::render([
                Schema::organisation(),
                Schema::breadcrumbs([['label' => lang('Catalog.pillars.' . $pillar . '_title')]]),
            ]),
            'title'           => lang('Catalog.pillars.' . $pillar . '_title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.pillars.' . $pillar . '_meta'),
        ]);
    }

    /**
     * Hang each category's own courses off it, and roll a subtree count up to
     * the branch above.
     *
     * The count has to include descendants or every branch heading reads zero:
     * a course sits in a leaf, and "Adobe Creative Cloud" itself holds none.
     *
     * @param list<array>              $nodes
     * @param array<int, list<array>>  $byCategory courses keyed by category id
     * @return list<array>
     */
    private function withCounts(array $nodes, array $byCategory): array
    {
        $out = [];

        foreach ($nodes as $node) {
            $node['children'] = $this->withCounts($node['children'] ?? [], $byCategory);
            $node['courses']  = $byCategory[(int) $node['id']] ?? [];
            $node['count']    = count($node['courses']) + array_sum(array_column($node['children'], 'count'));

            // A category with nothing published under it is left out rather
            // than shown with a zero beside it. The grid is a way in, and a way
            // in to an empty page is worse than one door fewer — it is also a
            // crawlable link to a thin page, which the SEO plan spends its
            // effort avoiding everywhere else.
            if ($node['count'] > 0) {
                $out[] = $node;
            }
        }

        return $out;
    }

    /**
     * The programmes that draw on this track.
     *
     * Bundles carry no pillar of their own — a programme is defined by the
     * courses in it — so membership is "contains at least one published course
     * in this pillar", expressed as a subquery.
     *
     * The course count is then a **second** query rather than a COUNT in the
     * first one, and that is the trap worth naming: joining `bundle_items` to
     * filter by pillar and counting in the same statement counts only the
     * courses that matched the filter. A nine-course programme with three
     * Adobe courses in it would print "3 courses in the programme" on the Adobe
     * hub and "6" on the AI one, and both figures would be wrong.
     *
     * @return list<array>
     */
    private function programmes(string $pillar, string $currency): array
    {
        $db = db_connect();

        $rows = $db->table('bundles b')
            ->select('b.*')
            ->where('b.status', 'published')
            ->whereIn('b.id', static function ($sub) use ($pillar) {
                return $sub->select('bi.bundle_id')
                    ->from('bundle_items bi')
                    ->join('courses c', 'c.id = bi.course_id')
                    ->where('c.pillar', $pillar)
                    ->where('c.status', 'published')
                    ->where('c.deleted_at IS NULL');
            })
            ->orderBy('b.sort_order', 'ASC')
            ->orderBy('b.id', 'ASC')
            ->get()->getResultArray();

        if ($rows === []) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));

        $countRows = $db->table('bundle_items bi')
            ->select('bi.bundle_id, COUNT(*) AS course_count', false)
            ->join('courses c', 'c.id = bi.course_id')
            ->whereIn('bi.bundle_id', $ids)
            ->where('c.deleted_at IS NULL')
            ->groupBy('bi.bundle_id')
            ->get()->getResultArray();
        $counts = array_column($countRows, 'course_count', 'bundle_id');

        $pricing = new PricingService();

        $out = [];
        foreach ($rows as $row) {
            $id   = (int) $row['id'];
            $type = (string) $row['type'];

            $out[] = $row + [
                // A type the SECTIONS map has never heard of would otherwise
                // build a link to /{locale}/ and quietly send buyers to the
                // home page. Skipped instead: see the loop below.
                'url'          => isset(self::SECTIONS[$type])
                    ? locale_url(self::SECTIONS[$type] . '/' . $row['slug'])
                    : null,
                'course_count' => (int) ($counts[$id] ?? 0),
                // The price only. The saving costs a query per course in the
                // programme, which is defensible on the page where the buying
                // decision is made and is not defensible on a hub that already
                // carries a catalogue, a schedule and a category tree. The
                // figure is on the programme's own page, one click away.
                'price'        => $pricing->bundlePrice($id, $currency),
            ];
        }

        return array_values(array_filter($out, static fn (array $row): bool => $row['url'] !== null));
    }

    /**
     * Add the "from" price and the next date to each featured course.
     *
     * Two grouped queries for the whole row rather than two per card. This is
     * the same shape as `Courses::decorate()` and is deliberately a second copy
     * of it: that method is private to the catalogue controller, and the
     * alternative — making it public, or moving it to the model — would put a
     * currency-aware presentation concern somewhere every other caller then has
     * to reason about.
     *
     * @param list<array> $rows
     * @return list<array>
     */
    private function decorateCourses(array $rows, string $currency): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));
        $db  = db_connect();

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

    /**
     * Price and seats for the hub's dates table.
     *
     * @param list<array> $rows
     * @return list<array>
     */
    private function decorateSessions(array $rows, PricingService $pricing, string $currency): array
    {
        if ($rows === []) {
            return [];
        }

        $cart      = CartContext::existing();
        $seatsLeft = (new InventoryService())->seatsLeftFor(
            array_map('intval', array_column($rows, 'id')),
            $cart ? (int) $cart['id'] : null
        );

        foreach ($rows as &$row) {
            $price = $pricing->sessionPrice((int) $row['id'], $currency);

            // Null rather than zero, and the view says so in words. A price of
            // nothing is a free class, which is a different and much more
            // expensive thing to publish by accident.
            $row['price_cents'] = $price['price_cents'] ?? null;
            $row['seats_left']  = $seatsLeft[(int) $row['id']] ?? null;
            $row['currency']    = $currency;
        }

        return $rows;
    }

    /**
     * The "at a glance" panel, derived rather than typed.
     *
     * Every figure is counted at render time, so none of them can flatter and
     * none of them goes stale. An entry that cannot be derived is left out: a
     * panel with three rows is honest, a panel with an invented fourth is the
     * kind of number a competitor screenshots.
     *
     * @return list<array{label:string, value:string}>
     */
    private function facts(string $pillar, int $courseCount, int $dateCount): array
    {
        $db    = db_connect();
        $facts = [];

        if ($courseCount > 0) {
            $facts[] = ['label' => lang('Catalog.pillars.fact_courses'), 'value' => (string) $courseCount];
        }
        if ($dateCount > 0) {
            $facts[] = ['label' => lang('Catalog.pillars.fact_dates'), 'value' => (string) $dateCount];
        }

        // What the courses in this track are *offered* in, not what happens to
        // be scheduled this month: a course available in person with no
        // classroom date in the calendar is still an in-person course, and the
        // panel is describing the track rather than the next fortnight.
        $modes = array_column(
            $db->table('course_delivery_modes m')
                ->select('m.mode')->distinct()
                ->join('courses c', 'c.id = m.course_id')
                ->where('c.pillar', $pillar)
                ->where('c.status', 'published')
                ->where('c.deleted_at IS NULL')
                ->get()->getResultArray(),
            'mode'
        );
        // Ordered by the constant rather than by whatever the database returned,
        // so the panel reads the same on every page of the site.
        $modes = array_values(array_intersect(CourseSessionModel::MODES, $modes));
        if ($modes !== []) {
            $facts[] = [
                'label' => lang('Catalog.pillars.fact_modes'),
                'value' => implode(' · ', array_filter(array_map('mode_label', $modes))),
            ];
        }

        $cap = (int) ($db->table('course_sessions cs')
            ->selectMax('cs.seats_total')
            ->join('courses c', 'c.id = cs.course_id')
            ->where('c.pillar', $pillar)
            ->where('cs.seats_total >', 0)
            ->get()->getRowArray()['seats_total'] ?? 0);
        if ($cap > 0) {
            $facts[] = ['label' => lang('Catalog.pillars.fact_class_size'), 'value' => (string) $cap];
        }

        return $facts;
    }

    /**
     * Each exam, with the courses that name it.
     *
     * The match runs against the **raw** `certification_alignment` column
     * rather than against `t_field()`'s resolved string, and that is
     * deliberate: the column is a JSON locale map, the product names inside it
     * are identical in every language, and a Sinhala reader must see the same
     * mapping an English one does long before the Sinhala copy is written.
     *
     * The three "not empty" tests are the same three the home page's fact bar
     * uses, for the same reason: an alignment nobody has written can arrive as
     * NULL, as an empty string, or as an empty JSON map, and a check for only
     * one of the three quietly counts courses that say nothing.
     *
     * @return list<array{app:string, match:string, courses:list<array>}>
     */
    private function examMap(): array
    {
        $rows = (new CourseModel())->live()
            ->select('courses.id, courses.slug, courses.title, courses.summary, courses.level, courses.duration_days, courses.duration_hours, courses.certification_alignment')
            ->where('courses.certification_alignment IS NOT NULL')
            ->where('courses.certification_alignment !=', '')
            ->where('courses.certification_alignment !=', '[]')
            ->where('courses.certification_alignment !=', '{}')
            ->orderBy('courses.level', 'ASC')
            ->orderBy('courses.id', 'ASC')
            ->findAll();

        $exams = [];
        foreach (self::EXAMS as $exam) {
            $matched = [];
            foreach ($rows as $row) {
                if (stripos((string) $row['certification_alignment'], $exam['match']) !== false) {
                    $matched[] = $row;
                }
            }

            $exams[] = $exam + ['courses' => $matched];
        }

        return $exams;
    }

    /**
     * The certification questions, as the shape `Schema::faqPage()` expects.
     *
     * They live in the language file rather than in `course_faqs` because they
     * are about the credential rather than about any one course: attaching them
     * to a course would answer a buyer's question about Photoshop with a
     * paragraph about Certiport's retake rules, and would repeat it on every
     * exam-aligned page.
     *
     * @return list<array{question:string, answer:string}>
     */
    private function faqs(): array
    {
        $lines = lang('Catalog.pillars.cert_faqs');

        // lang() answers a key it cannot find with the key itself, and a
        // translator can turn an array into a string. Either would be a
        // foreach over a string; both are simply no FAQ section.
        if (! is_array($lines)) {
            return [];
        }

        $faqs = [];
        foreach ($lines as $line) {
            if (! is_array($line) || empty($line['q']) || empty($line['a'])) {
                continue;
            }
            $faqs[] = ['question' => (string) $line['q'], 'answer' => (string) $line['a']];
        }

        return $faqs;
    }
}
