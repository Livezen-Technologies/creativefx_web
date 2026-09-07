<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\CourseModel;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Catalog\Models\VenueModel;
use Modules\Catalog\Models\WaitlistModel;
use Modules\Commerce\Libraries\CartContext;
use Modules\Commerce\Services\InventoryService;
use Modules\Commerce\Services\PricingService;

/**
 * The calendar, one date on it, and the list you join when there is no date.
 *
 * The course page sells a course; this page sells a *diary*. Somebody arriving
 * at /schedule has already decided they want to learn something and is now
 * asking a scheduling question — which week, which city, is it running — and
 * the answer has to be scannable rather than persuasive. So the calendar is one
 * long table grouped by month with a sticky month heading, not a grid of cards:
 * a person comparing the 14th with the 28th is reading down a column.
 *
 * Three decisions are made here rather than in the views, because each of them
 * is a query and none of them belongs in a loop:
 *
 *   1. **Seats are counted once for the whole page**, through
 *      `InventoryService::seatsLeftFor()`. Eighty dates asked one at a time is
 *      eighty queries plus eighty more for the holds, which is exactly how a
 *      schedule page becomes the slowest page on a training site.
 *   2. **Every row is priced in the visitor's own currency**, and a date with
 *      no price in it is still shown. That is the one place this page
 *      deliberately diverges from the course page, which drops unpriced
 *      sessions: the course page is answering "what does it cost", so a
 *      priceless row there is noise, but this page is answering "when does it
 *      run", and hiding a real date because a price list is incomplete is
 *      losing a booking to an admin oversight.
 *   3. **A full row is not a dead end.** It offers the waitlist in the same
 *      column the Book button would have been in, because the moment somebody
 *      learns the class is full is the only moment they will ever be willing to
 *      leave an address.
 */
class Schedule extends BaseController
{
    /** Rows per page. Long enough that a month is rarely split across two. */
    private const PER_PAGE = 30;

    /**
     * How many waitlist submissions one address, and one machine, may make.
     *
     * Generous by design: the throttle exists to stop the table being filled by
     * a script, not to inconvenience somebody who mistyped their email and
     * submitted twice. The machine limit is the wider of the two because an
     * office, a campus or a mobile carrier is one address to us and many people
     * to itself.
     */
    private const WAITLIST_PER_EMAIL = 5;
    private const WAITLIST_PER_IP    = 20;

    // ── The calendar ────────────────────────────────────────────────────────

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $sessions = new CourseSessionModel();
        $pricing  = new PricingService();
        $currency = current_currency();

        $filters = $this->readFilters();
        $page    = max(1, (int) $this->request->getGet('page'));

        $result = $sessions->schedule($filters, self::PER_PAGE, $page);
        $rows   = $this->decorate($result['rows'], $pricing, $currency);

        $crumbs = [['label' => lang('Catalog.schedule.title')]];

        // Canonical is built rather than left to the layout's `current_url()`,
        // which drops the query string: without it /schedule?page=3 would
        // declare page 1 canonical and tell a crawler that pages 2 onwards hold
        // nothing worth keeping. It stays self-referential even on a filtered
        // view, because a noindex page that names a *different* canonical sends
        // two contradictory instructions about the same document.
        $canonical = locale_url('schedule') . $this->queryString($filters, $page);

        return view('Modules\Catalog\Views\schedule\index', [
            'months'          => $this->groupByMonth($rows),
            'total'           => $result['total'],
            'page'            => $page,
            'perPage'         => self::PER_PAGE,
            'filters'         => $filters,
            'currency'        => $currency,
            'modes'           => CourseSessionModel::MODES,
            'cities'          => $this->cityOptions(),
            'courses'         => $this->courseOptions(),
            'months_options'  => $this->monthOptions(),
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => lang('Catalog.schedule.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.schedule.meta'),
            'canonical'       => $canonical,
            // A facet combination is a slice of a page that already exists, and
            // five filters multiply into thousands of near-identical addresses.
            // The unfiltered calendar is the page worth indexing; the slices are
            // for the visitor who asked for them.
            'noIndex'         => $filters !== [],
        ]);
    }

    // ── One date ────────────────────────────────────────────────────────────

    /**
     * A single session, at /schedule/{course-slug}-{id}.
     *
     * The slug is prose and the trailing id is what resolves, so renaming a
     * course rewrites the readable half of every one of its session URLs
     * without breaking a link somebody has already bookmarked or a crawler has
     * already stored. Only the id is trusted: a mismatched slug is redundant
     * decoration, not a 404, for the same reason.
     */
    public function show(?string $locale = null, ?string $segment = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        // The id is the run of digits at the very end. Anchoring to the end
        // matters: a course slug such as "after-effects-2025" has digits in the
        // middle, and a looser pattern would resolve that page to session 2025.
        if (preg_match('~-(\d+)$~', (string) $segment, $matches) !== 1) {
            throw PageNotFoundException::forPageNotFound();
        }

        $sessions = new CourseSessionModel();
        $session  = $sessions->detail((int) $matches[1]);

        if ($session === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        // Private dates are a company's own class, arranged by quote. They have
        // a row and a URL like any other session, and publishing that URL would
        // put one client's booking — their dates, their venue, their headcount
        // — on the open web.
        if (! empty($session['is_private'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        // `detail()` joins the course without filtering it, so an unpublished
        // or soft-deleted course would still render one of its dates. Checking
        // through the live scope is what keeps a draft course off the site by
        // way of its own schedule.
        $courses = new CourseModel();
        $course  = $courses->live()->where('courses.id', (int) $session['course_id'])->first();
        if ($course === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $currency = current_currency();
        $price    = (new PricingService())->sessionPrice((int) $session['id'], $currency);
        $cart     = CartContext::existing();

        $session['price_cents']      = $price['price_cents'] ?? null;
        $session['compare_at_cents'] = $price['compare_at_cents'] ?? null;
        $session['seats_left']       = (new InventoryService())->seatsLeft(
            (int) $session['id'],
            $cart ? (int) $cart['id'] : null
        );

        // The venue row itself, for its address, its directions and the city
        // page it belongs to — `detail()` brings the name and city across for
        // the table but not the slug that links the two pages together.
        $venue = empty($session['venue_id'])
            ? null
            : (new VenueModel())->find((int) $session['venue_id']);

        $detail = $courses->detail((int) $course['id']);

        $crumbs = [
            ['label' => lang('Catalog.schedule.title'), 'url' => locale_url('schedule')],
            ['label' => t_field($course['title']), 'url' => course_url($course['slug'])],
            ['label' => session_dates($session)],
        ];

        return view('Modules\Catalog\Views\schedule\show', [
            'session'         => $session,
            'course'          => $course,
            'detail'          => $detail,
            'venue'           => $venue,
            'days'            => $session['days'],
            'currency'        => $currency,
            'bookable'        => $this->isBookable($session),
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([
                Schema::organisation(),
                Schema::event($session, $course, $currency),
                Schema::breadcrumbs($crumbs),
            ]),
            'title'           => lang('Catalog.session.title', [t_field($course['title']), session_dates($session)])
                . ' — ' . setting('site_name', ''),
            'metaDescription' => t_field($course['summary']),
            'canonical'       => $this->canonicalFor($session, $course),
            'ogImage'         => $course['hero_image'] ? media_src($course['hero_image']) : null,
            'lastUpdated'     => $session['updated_at'],
        ]);
    }

    // ── The waitlist ────────────────────────────────────────────────────────

    /**
     * Somebody wanted to buy and could not.
     *
     * Posted from three places — the booking panel when a mode has no dates,
     * the dates table when a date is full, and the session page itself — so it
     * accepts either a session or a course and works out the other where it
     * can. It is deliberately open to signed-out visitors: requiring an account
     * before you may say "tell me when this runs" loses the lead the form
     * exists to capture.
     */
    public function waitlist(?string $locale = null): RedirectResponse
    {
        helper(['norlanka', 'catalog', 'url']);

        $sessionId = (int) $this->request->getPost('session_id');
        $courseId  = (int) $this->request->getPost('course_id');

        $sessions = new CourseSessionModel();
        $session  = $sessionId > 0 ? $sessions->find($sessionId) : null;

        // A posted id that resolves to nothing is a forged or stale form, not a
        // user error, so it gets the 404 a made-up URL would get rather than a
        // message telling somebody how to guess a valid one.
        // A private date does not exist as far as the public site is concerned —
        // `show()` refuses to render one — so it cannot be waitlisted either.
        // Without this the form would accept an id nobody could have seen and
        // put junk rows against a client's own class.
        if ($sessionId > 0 && ($session === null || ! empty($session['is_private']))) {
            throw PageNotFoundException::forPageNotFound();
        }
        if ($session !== null) {
            // The session decides the course, whatever the form claimed: the
            // course_id field is a convenience for the redirect, not a fact to
            // be trusted from a POST body.
            $courseId = (int) $session['course_id'];
        }

        $course = $courseId > 0 ? (new CourseModel())->find($courseId) : null;
        if ($course === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $back = $this->returnUrl($session, $course);

        if (! $this->validate([
            'email' => 'required|valid_email|max_length[191]',
            'name'  => 'permit_empty|max_length[128]',
            'note'  => 'permit_empty|max_length[2000]',
        ])) {
            return redirect()->to($back)
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('waitlist_error', lang('Catalog.waitlist.invalid'));
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));

        // Throttled on the address and on the machine. Without this the form is
        // an unauthenticated insert with no cost attached to it, and a table of
        // email addresses is exactly what somebody would choose to fill.
        $throttle = service('throttler');
        $allowed  = $throttle->check(md5('waitlist-' . $email), self::WAITLIST_PER_EMAIL, HOUR)
            && $throttle->check(md5('waitlist-ip-' . $this->request->getIPAddress()), self::WAITLIST_PER_IP, HOUR);

        if (! $allowed) {
            return redirect()->to($back)->withInput()->with('waitlist_error', lang('Catalog.waitlist.throttled'));
        }

        (new WaitlistModel())->register([
            'session_id' => $session === null ? null : (int) $session['id'],
            'course_id'  => (int) $course['id'],
            'user_id'    => LearnerAuth::id(),
            'name'       => $this->request->getPost('name'),
            'email'      => $email,
            'note'       => $this->request->getPost('note'),
        ]);

        // A duplicate gets the same confirmation as a first entry, because from
        // the sender's side both are true: they are on the list and they will be
        // told. Saying "you are already on this list" only invites the reply
        // "then why have I heard nothing", which is a support ticket rather
        // than a lead.
        return redirect()->to($back)->with('waitlist_ok', lang('Catalog.waitlist.added'));
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * The facets, validated.
     *
     * An unknown value is a 404 rather than a quietly dropped parameter, for
     * the same reason as on the catalogue: /schedule?mode=nonsense returning
     * the whole calendar is indistinguishable from a filter that matched
     * everything, and it is a bug nobody reports because the page looks fine.
     *
     * @return array{mode?:string, pillar?:string, city?:string, course?:int, from?:string, to?:string, month?:string}
     */
    private function readFilters(): array
    {
        $filters = [];

        $mode = trim((string) $this->request->getGet('mode'));
        if ($mode !== '') {
            if (! in_array($mode, CourseSessionModel::MODES, true)) {
                throw PageNotFoundException::forPageNotFound();
            }
            $filters['mode'] = $mode;
        }

        $pillar = trim((string) $this->request->getGet('pillar'));
        if ($pillar !== '') {
            if (! in_array($pillar, ['adobe', 'ai', 'design'], true)) {
                throw PageNotFoundException::forPageNotFound();
            }
            $filters['pillar'] = $pillar;
        }

        // The city is checked against the cities that actually have a venue,
        // rather than passed through: an arbitrary string reaching the query is
        // both an empty page and an open text parameter in the index.
        $city = trim((string) $this->request->getGet('city'));
        if ($city !== '') {
            if (! in_array($city, $this->cityOptions(), true)) {
                throw PageNotFoundException::forPageNotFound();
            }
            $filters['city'] = $city;
        }

        // Checked against the courses that actually appear on this calendar,
        // like the city is. An unchecked id renders an empty page carrying a
        // waitlist form for a course that does not exist, which is a row in the
        // table nobody can ever act on.
        $course = (int) $this->request->getGet('course');
        if ($course > 0) {
            if (! in_array($course, array_column($this->courseOptions(), 'id'), true)) {
                throw PageNotFoundException::forPageNotFound();
            }
            $filters['course'] = $course;
        }

        // One month, expressed to the model as the range it really is. The
        // last day is derived rather than assumed to be the 30th, because the
        // alternative loses every class on the 31st of five months a year.
        $month = trim((string) $this->request->getGet('month'));
        if ($month !== '') {
            if (preg_match('~^\d{4}-\d{2}$~', $month) !== 1) {
                throw PageNotFoundException::forPageNotFound();
            }
            $first = DateTimeImmutable::createFromFormat('Y-m-d', $month . '-01');
            if ($first === false) {
                throw PageNotFoundException::forPageNotFound();
            }
            $filters['month'] = $month;
            $filters['from']  = $first->format('Y-m-d');
            $filters['to']    = $first->format('Y-m-t');
        }

        return $filters;
    }

    /**
     * The facets and the page, back as a query string.
     *
     * `from` and `to` are derived from `month` and are deliberately left out:
     * emitting all three would give the same view two addresses, which is the
     * duplicate a canonical exists to prevent.
     */
    private function queryString(array $filters, int $page): string
    {
        $query = array_filter([
            'mode'   => $filters['mode'] ?? null,
            'pillar' => $filters['pillar'] ?? null,
            'city'   => $filters['city'] ?? null,
            'course' => $filters['course'] ?? null,
            'month'  => $filters['month'] ?? null,
            'page'   => $page > 1 ? $page : null,
        ]);

        return $query === [] ? '' : '?' . http_build_query($query);
    }

    /**
     * Price every row and count every seat.
     *
     * The seat counts are one call for the whole page; the prices are one small
     * indexed lookup per row, because `PricingService` publishes no batch
     * equivalent of `seatsLeftFor()` and inlining the query here would put a
     * second, divergent definition of "the price" in the codebase.
     *
     * @param list<array> $rows
     * @return list<array>
     */
    private function decorate(array $rows, PricingService $pricing, string $currency): array
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
            $row['price_cents']      = $price['price_cents'] ?? null;
            $row['compare_at_cents'] = $price['compare_at_cents'] ?? null;
            $row['seats_left']       = $seatsLeft[(int) $row['id']] ?? null;
            $row['currency']         = $currency;
        }

        return $rows;
    }

    /**
     * Break the page into months, in the order the rows already arrive in.
     *
     * The model sorts undated rows last, so the self-paced bucket falls at the
     * end of its own accord and is keyed on the empty string — a month heading
     * over "start any time" would be a lie about a date that does not exist.
     *
     * @param list<array> $rows
     * @return list<array{key:string, label:string, rows:list<array>}>
     */
    private function groupByMonth(array $rows): array
    {
        $months = [];

        foreach ($rows as $row) {
            $key = empty($row['start_date'])
                ? ''
                : (new DateTimeImmutable((string) $row['start_date']))->format('Y-m');

            if (! isset($months[$key])) {
                $months[$key] = [
                    'key'   => $key,
                    'label' => $key === ''
                        ? lang('Catalog.session.any_time')
                        : (new DateTimeImmutable($key . '-01'))->format('F Y'),
                    'rows'  => [],
                ];
            }

            $months[$key]['rows'][] = $row;
        }

        return array_values($months);
    }

    /**
     * The next twelve months, as filter options.
     *
     * Built from the calendar rather than from `now`, so the dropdown offers
     * months that have something in them: a list of twelve months of which ten
     * are empty teaches a visitor that the filter does not work.
     *
     * @return list<array{value:string, label:string}>
     */
    private function monthOptions(): array
    {
        // `substr` rather than `SUBSTRING`: the former is spelled the same way
        // in SQLite and in MySQL, and this site runs on both — SQLite locally
        // and in the test suite, MySQL on the server.
        $rows = db_connect()->table('course_sessions cs')
            ->select('DISTINCT substr(cs.start_date, 1, 7) AS ym', false)
            ->join('courses c', 'c.id = cs.course_id')
            ->where('cs.is_private', 0)
            ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
            ->where('cs.start_date >=', date('Y-m-d'))
            ->where('c.status', 'published')
            ->where('c.deleted_at IS NULL')
            ->orderBy('ym', 'ASC')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            if (empty($row['ym'])) {
                continue;
            }
            $out[] = [
                'value' => (string) $row['ym'],
                'label' => (new DateTimeImmutable($row['ym'] . '-01'))->format('F Y'),
            ];
        }

        return $out;
    }

    /**
     * The cities with a published classroom in them.
     *
     * @return list<string>
     */
    private function cityOptions(): array
    {
        static $cities = null;
        if ($cities !== null) {
            return $cities;
        }

        $rows = db_connect()->table('venues')
            ->select('DISTINCT city', false)
            ->where('status', 'published')
            ->where('city IS NOT NULL')
            ->where('city !=', '')
            ->orderBy('city', 'ASC')
            ->get()->getResultArray();

        return $cities = array_values(array_map('strval', array_column($rows, 'city')));
    }

    /**
     * Courses that have at least one date on this calendar.
     *
     * Listing the whole catalogue here would offer a hundred choices of which
     * most select an empty page — the dropdown should only be able to produce
     * a result.
     *
     * @return list<array{id:int, title:string}>
     */
    private function courseOptions(): array
    {
        // Memoised: the filter validation asks for this list and so does the
        // dropdown, and it is the most expensive of the three option queries.
        static $options = null;
        if ($options !== null) {
            return $options;
        }

        $rows = db_connect()->table('courses c')
            ->select('c.id, c.title')
            ->join('course_sessions cs', 'cs.course_id = c.id')
            ->where('cs.is_private', 0)
            ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
            ->groupStart()->where('cs.start_date IS NULL')->orWhere('cs.start_date >=', date('Y-m-d'))->groupEnd()
            ->where('c.status', 'published')
            ->where('c.deleted_at IS NULL')
            ->groupBy('c.id, c.title')
            ->get()->getResultArray();

        $out = [];
        foreach ($rows as $row) {
            $out[] = ['id' => (int) $row['id'], 'title' => t_field($row['title'])];
        }

        // Sorted on the resolved title rather than in SQL, because the column
        // is a JSON locale map and ordering by it orders by whichever language
        // happens to be first in the JSON.
        usort($out, static fn (array $a, array $b): int => strcasecmp($a['title'], $b['title']));

        return $options = $out;
    }

    /**
     * Where a session page points its canonical.
     *
     * A course that runs monthly produces a dozen session pages a year whose
     * only differing content is a date. Left to themselves they compete with
     * the course page — the page the whole site is built to rank — and split
     * its authority a dozen ways. So by default a session page declares the
     * course page canonical and exists for the person who followed a link to
     * it, not for the index.
     *
     * The exception is a session that carries something the course page cannot:
     * a physical venue, with a city and an address, is a genuinely different
     * page and the one a local search is looking for; and a session with its
     * own notes carries editorial text that exists nowhere else. Those stand on
     * their own.
     *
     * The Event markup is emitted either way. On a self-canonical page it is
     * the point of the page; on a canonicalised one it is simply true of what
     * is rendered, which is the only test structured data has to pass.
     */
    private function canonicalFor(array $session, array $course): string
    {
        $unique = ! empty($session['venue_id']) || trim((string) ($session['notes'] ?? '')) !== '';

        return $unique ? session_url($session) : course_url($course['slug']);
    }

    /** Whether a seat on this date can actually be bought right now. */
    private function isBookable(array $session): bool
    {
        if (! in_array((string) $session['status'], CourseSessionModel::BOOKABLE, true)) {
            return false;
        }

        // A dated class that has already started is not a purchase, whatever
        // its status column still says.
        if (! empty($session['start_date']) && $session['start_date'] < date('Y-m-d')) {
            return false;
        }

        return ($session['seats_left'] ?? null) !== 0;
    }

    /**
     * Where a waitlist submission goes back to.
     *
     * The referrer when it is one of our own pages — the visitor was reading a
     * course page two thousand pixels down and should land back on it — and the
     * session or course page otherwise. `redirect()->back()` is not used
     * directly because its fallback is the site root, which for a form posted
     * without a referrer header loses both the context and the flash.
     */
    private function returnUrl(?array $session, array $course): string
    {
        $referrer = (string) ($this->request->getServer('HTTP_REFERER') ?? '');
        if ($referrer !== '' && str_starts_with($referrer, rtrim(base_url(), '/') . '/')) {
            return $referrer;
        }

        return $session === null
            ? course_url($course['slug'])
            : session_url($session + ['course_slug' => $course['slug']]);
    }
}
