<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\CourseModel;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Commerce\Services\PricingService;
use Modules\Learning\Models\LessonModel;

/**
 * The self-paced library, at /on-demand.
 *
 * A dated course sells on its calendar; a self-paced one has no calendar to
 * sell on, so the three facts that replace it are decided here rather than in
 * the views, because each of them is a query and none belongs inside a loop
 * over cards:
 *
 *   1. **The free preview.** It is the single biggest conversion lever on an
 *      on-demand catalogue — somebody who has watched a lesson has already
 *      decided whether the teaching suits them, which is the question the
 *      description can only assert an answer to. So the preview is resolved
 *      for the whole grid in one query and the card links straight at the
 *      lesson rather than at a page that mentions one.
 *   2. **The lesson count**, in one grouped query for the whole page. Asked
 *      per card it is thirty queries on a thirty-course library, which is the
 *      ordinary way a listing page becomes the slowest page on a site.
 *   3. **The self-paced price**, in the visitor's own currency, from the
 *      SELF_PACED session — the same session the buy button books, so the
 *      figure on the card and the figure taken at checkout cannot diverge.
 *
 * Nothing here is invented for a library that has not been recorded yet. At
 * launch there are sessions and prices but no `lessons` rows at all, so the
 * counts are zero, the preview is absent, and the views say so in words rather
 * than printing "0 lessons" or opening an empty accordion.
 */
class OnDemand extends BaseController
{
    /**
     * The library.
     *
     * Unpaginated on purpose. The catalogue at /courses runs to a hundred rows
     * across every mode and needs pages; the self-paced shelf is a subset of it
     * that a buyer is meant to be able to read in one scroll, and splitting a
     * short list across pages hides half of it behind a click for no gain.
     */
    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $currency = current_currency();

        // A sub-select rather than a join with a GROUP BY: a course with three
        // self-paced sessions must still appear once, and de-duplicating in
        // SQL would mean grouping every selected column of `courses`.
        $courses = (new CourseModel())->live()
            ->whereIn('courses.id', static function ($sub) {
                return $sub->select('course_id')->from('course_sessions')
                    ->where('mode', 'SELF_PACED')
                    ->where('is_private', 0)
                    ->whereIn('status', CourseSessionModel::BOOKABLE);
            })
            ->orderBy('courses.pillar', 'ASC')
            ->orderBy('courses.level', 'ASC')
            ->orderBy('courses.id', 'ASC')
            ->findAll();

        $crumbs = [['label' => lang('Catalog.ondemand.title')]];

        return view('Modules\Catalog\Views\ondemand\index', [
            'courses'         => $this->decorate($courses, $currency),
            'currency'        => $currency,
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => lang('Catalog.ondemand.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.ondemand.meta'),
        ]);
    }

    /**
     * One course, seen as an on-demand product.
     *
     * Deliberately not a second course page. It answers the four questions a
     * self-paced buyer has and the dated page cannot: what is in it, how long
     * it is, what one lesson of it actually looks like, and what it costs to
     * start today.
     */
    public function show(?string $locale = null, ?string $slug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $courses = new CourseModel();
        $course  = $courses->findLive((string) $slug);
        if ($course === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $courseId = (int) $course['id'];
        $detail   = $courses->detail($courseId);
        $currency = current_currency();

        // There is normally exactly one self-paced session per course — it
        // carries no dates and no seat limit — so the lowest id wins if an
        // editor has ever created a second, and the page and the buy button
        // then agree on which one is being sold.
        $session = (new CourseSessionModel())->open()
            ->where('course_sessions.course_id', $courseId)
            ->where('course_sessions.mode', 'SELF_PACED')
            ->orderBy('course_sessions.id', 'ASC')
            ->first();

        // A course sold only in a classroom has no on-demand page at all. But a
        // course that *is* sold self-paced and has simply had its session
        // withdrawn keeps one, showing the leave-your-address form: a 404 there
        // would throw away a visitor who arrived from a search for exactly this
        // course and exactly this way of taking it.
        if ($session === null && ! in_array('SELF_PACED', $detail['modes'], true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $price = $session === null
            ? null
            : (new PricingService())->sessionPrice((int) $session['id'], $currency);

        $lessons = new LessonModel();

        // `outline()` returns every curriculum module, including the ones
        // nothing has been recorded against yet, because the player needs the
        // full shape. A sales page does not: a module heading with nothing
        // under it reads as a course with holes in it, so the empty ones are
        // dropped here and the view decides between an outline and an honest
        // sentence on whether anything survived.
        $outline = array_values(array_filter(
            $lessons->outline($courseId),
            static fn (array $module): bool => ($module['lessons'] ?? []) !== []
        ));

        $lessonCount = 0;
        $runtimeSec  = 0;
        $lessonIds   = [];
        foreach ($outline as $module) {
            foreach ($module['lessons'] as $lesson) {
                $lessonCount++;
                $runtimeSec += (int) $lesson['duration_sec'];
                $lessonIds[] = (int) $lesson['id'];
            }
        }

        $crumbs = [
            ['label' => lang('Catalog.ondemand.title'), 'url' => locale_url('on-demand')],
            ['label' => t_field($course['title'])],
        ];

        // Whether this page speaks for the course or defers to the course page.
        // One decision, used twice: it settles the canonical, and with it which
        // of the two pages carries the Course graph. See isPrimaryPage().
        $isPrimary = $this->isPrimaryPage($lessonCount);

        return view('Modules\Catalog\Views\ondemand\show', [
            'course'          => $course,
            'detail'          => $detail,
            'session'         => $session,
            'price'           => $price,
            'currency'        => $currency,
            'outline'         => $outline,
            'lessonCount'     => $lessonCount,
            'runtimeSec'      => $runtimeSec,
            'preview'         => $lessons->previewFor($courseId),
            'assets'          => $this->assetsByLesson($lessonIds),
            'crumbs'          => $crumbs,
            'schema'          => Schema::render(array_merge(
                [Schema::organisation(), Schema::breadcrumbs($crumbs)],
                // The Course graph goes on whichever page is canonical for this
                // course, and only there. Two URLs both describing the same
                // Course with different offers attached is an invitation to a
                // search engine to list the wrong one. The offer is the
                // self-paced price this page actually shows: an offers block a
                // visitor cannot see on the page is the mismatch that gets
                // structured data ignored.
                $isPrimary && $session !== null && $price !== null
                    ? [Schema::course(
                        $course,
                        [$session + ['price_cents' => $price['price_cents'], 'course_slug' => $course['slug']]],
                        $currency
                    )]
                    : []
            )),
            'title'           => lang('Catalog.session.title', [t_field($course['title']), mode_label('SELF_PACED')])
                . ' — ' . setting('site_name', ''),
            'metaDescription' => t_field($course['summary']),
            'canonical'       => $isPrimary
                ? locale_url('on-demand/' . $course['slug'])
                : course_url($course['slug']),
            'ogImage'         => $course['hero_image'] ? media_src($course['hero_image']) : null,
            'lastUpdated'     => $course['updated_at'],
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Add the price, the lesson count and the preview to each card.
     *
     * Three queries for the whole grid regardless of its size, which is the
     * entire point of doing it here: the same work asked per card is three
     * queries multiplied by the length of the library.
     *
     * @param list<array> $rows
     * @return list<array>
     */
    private function decorate(array $rows, string $currency): array
    {
        if ($rows === []) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));
        $db  = db_connect();

        // The self-paced price per course, taken from the *same* session the
        // on-demand page will sell — the lowest-id bookable one, which is the
        // rule show() applies too. Quoting MIN(price) across a course's
        // self-paced sessions would be a cheaper number on the card than the
        // one the buy button charges the moment an editor creates a second.
        $priceRows = $db->table('course_sessions cs')
            ->select('cs.course_id, sp.price_cents', false)
            ->join('session_prices sp', 'sp.session_id = cs.id')
            ->where('sp.currency', $currency)
            ->whereIn('cs.id', static function ($sub) use ($ids) {
                return $sub->select('MIN(id)', false)->from('course_sessions')
                    ->whereIn('course_id', $ids)
                    ->where('mode', 'SELF_PACED')
                    ->where('is_private', 0)
                    ->whereIn('status', CourseSessionModel::BOOKABLE)
                    ->groupBy('course_id');
            })
            ->get()->getResultArray();
        $prices = array_column($priceRows, 'price_cents', 'course_id');

        // The lesson counts, grouped — one query for the page, not one per card.
        $countRows = $db->table('lessons')
            ->select('course_id, COUNT(*) AS lessons', false)
            ->whereIn('course_id', $ids)
            ->where('status', 'published')
            ->groupBy('course_id')
            ->get()->getResultArray();
        $counts = array_column($countRows, 'lessons', 'course_id');

        // The preview lesson itself rather than a flag on the count above: the
        // card links straight into the lesson, and a badge saying a preview
        // exists without saying where it is wastes the only free thing the page
        // has to offer.
        $previewRows = $db->table('lessons')
            ->select('course_id, slug')
            ->whereIn('course_id', $ids)
            ->where('is_preview', 1)
            ->where('status', 'published')
            ->orderBy('course_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $previews = [];
        foreach ($previewRows as $row) {
            // Ordered by sort_order, so the first row seen for a course is its
            // earliest preview and later ones are ignored.
            $previews[(int) $row['course_id']] ??= (string) $row['slug'];
        }

        foreach ($rows as &$row) {
            $row['price_cents']  = isset($prices[$row['id']]) ? (int) $prices[$row['id']] : null;
            $row['lesson_count'] = isset($counts[$row['id']]) ? (int) $counts[$row['id']] : 0;
            $row['preview_slug'] = $previews[(int) $row['id']] ?? null;
            $row['currency']     = $currency;
        }
        unset($row);

        return $rows;
    }

    /**
     * Every exercise file in the outline, in one query, keyed by lesson.
     *
     * Labels only — the files themselves are behind the enrolment check in the
     * player. Naming what is in the box is selling; handing it over before it
     * is bought is not.
     *
     * @param list<int> $lessonIds
     * @return array<int, list<array>>
     */
    private function assetsByLesson(array $lessonIds): array
    {
        if ($lessonIds === []) {
            return [];
        }

        $rows = db_connect()->table('lesson_assets')
            ->whereIn('lesson_id', $lessonIds)
            ->orderBy('lesson_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['lesson_id']][] = $row;
        }

        return $out;
    }

    /**
     * Whether this page is the primary one for the course.
     *
     * Once lessons are recorded, it carries an outline, a runtime and a free
     * lesson that exist nowhere else, and it is the page somebody searching for
     * a self-paced course should land on. So it names itself canonical and
     * takes the Course graph.
     *
     * Before then — which is the state at launch — it is a thinner restatement
     * of the course page, differing only in which price it leads with. Left
     * self-canonical it would compete with the money page for the same query
     * and split its authority in two. The same reasoning governs session pages;
     * see Schedule::canonicalFor().
     */
    private function isPrimaryPage(int $lessonCount): bool
    {
        return $lessonCount > 0;
    }
}
