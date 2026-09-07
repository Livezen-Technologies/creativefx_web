<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\CourseSessionModel;
use Modules\Catalog\Models\VenueModel;
use Modules\Commerce\Libraries\CartContext;
use Modules\Commerce\Services\InventoryService;
use Modules\Commerce\Services\PricingService;

/**
 * The city pages: where classes actually happen.
 *
 * Local search is the cheapest traffic a training school will ever buy, and it
 * is also the easiest thing on a site to get wrong. Four pages built from one
 * template with the city name swapped is precisely the shape a search engine
 * looks for when deciding a site is thin, so the prose on these pages is a
 * column in the database (`venues.body`) rather than a string in this file, and
 * it is genuinely different per city: what the Colombo market buys is not what
 * the Dubai market buys, and the Kandy page exists to argue that nobody should
 * have to drive to Colombo.
 *
 * Three decisions live here rather than in the views:
 *
 *   1. **The online room adopts the dates nobody placed.** A live online class
 *      has no venue to travel to, so it is created with `venue_id` null — by
 *      the seeder and, in practice, by anybody adding a date in the admin. Ask
 *      only for rows pointing at the virtual venue and the busiest "location"
 *      on the site reports that nothing is scheduled. See `atVenue()`.
 *   2. **The address may not be printable.** It is often the copy's own
 *      "{to be confirmed}" placeholder, and printing that as a street address —
 *      on the page or, worse, in structured data — is how somebody ends up
 *      outside the wrong building on the morning of a course they paid for. See
 *      `publishableAddress()`.
 *   3. **Prices and seats are resolved once for the whole page.** A city page
 *      lists a dozen dates; asking each one for its price and its remaining
 *      seats separately is how a landing page becomes the slowest page on the
 *      site.
 */
class Locations extends BaseController
{
    /** Dates listed on a city page. Enough to prove a schedule, not a calendar. */
    private const UPCOMING = 12;

    /** Courses on the "what we run here" shelf. Two rows of three at desktop. */
    private const POPULAR = 6;

    // ── Every place we teach ────────────────────────────────────────────────

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $venues = $this->summarise((new VenueModel())->live()->findAll());
        $crumbs = [['label' => lang('Catalog.locations.title')]];

        return view('Modules\Catalog\Views\locations\index', [
            'venues'          => $venues,
            'total'           => array_sum(array_column($venues, 'date_count')),
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => lang('Catalog.locations.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.locations.meta'),
        ]);
    }

    // ── One city ────────────────────────────────────────────────────────────

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $venue = (new VenueModel())->findLive((string) $slug);
        if ($venue === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $adopts   = $this->isOnlineRoom($venue);
        $currency = current_currency();
        $pricing  = new PricingService();

        $sessions = $this->decorate($this->upcomingFor($venue, $adopts), $pricing, $currency);
        $address  = $this->publishableAddress($venue);

        $crumbs = [
            ['label' => lang('Catalog.locations.title'), 'url' => locale_url('locations')],
            ['label' => (string) ($venue['city'] ?: $venue['name'])],
        ];

        return view('Modules\Catalog\Views\locations\show', [
            'venue'        => $venue,
            // Passed already resolved rather than as a raw column, so neither
            // the page nor the JSON-LD can print a placeholder by forgetting to
            // ask the question.
            'address'      => $address,
            'isOnlineRoom' => $adopts,
            'sessions'     => $sessions,
            'courses'      => $this->popularCourses($venue, $adopts, $currency),
            'currency'     => $currency,
            'crumbs'       => $crumbs,
            'schema'       => Schema::render([
                Schema::organisation(),
                $this->place($venue, $address),
                Schema::breadcrumbs($crumbs),
            ]),
            'title'           => t_field($venue['seo_title']) ?: (t_field($venue['heading']) ?: $venue['name']) . ' — ' . setting('site_name', ''),
            'metaDescription' => t_field($venue['seo_description']) ?: t_field($venue['summary']),
            'metaKeywords'    => (string) ($venue['seo_keywords'] ?? ''),
            'lastUpdated'     => $venue['updated_at'] ?? null,
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Restrict a session query to one venue.
     *
     * The `$adoptsUnplaced` branch is the whole reason this is a method. A
     * class nobody travels to has no venue: `course_sessions.venue_id` is null
     * on every live online date the seeder writes, and on every one an
     * administrator creates without picking a room, because there is no room to
     * pick. Matching on `venue_id` alone therefore returns nothing for the
     * virtual venue, and the page for the delivery mode most of the catalogue
     * is sold in would say "nothing is scheduled here at the moment".
     *
     * Only one venue adopts them — see `isOnlineRoom()` — because two virtual
     * rooms both claiming every unplaced date would make both pages wrong.
     */
    private function atVenue(BaseBuilder $builder, int $venueId, bool $adoptsUnplaced): BaseBuilder
    {
        if (! $adoptsUnplaced) {
            return $builder->where('cs.venue_id', $venueId);
        }

        // The mode test is load-bearing: self-paced sessions also carry a null
        // venue_id, and a course somebody buys to watch at midnight has no
        // business on a page about a room with a timetable.
        return $builder
            ->groupStart()
                ->where('cs.venue_id', $venueId)
                ->orGroupStart()
                    ->where('cs.venue_id IS NULL')
                    ->where('cs.mode', 'LIVE_ONLINE')
                ->groupEnd()
            ->groupEnd();
    }

    /**
     * Whether this venue is the one online room that speaks for unplaced live
     * online dates.
     *
     * The first published virtual venue, by the ordering `live()` already
     * applies, so the answer is stable between the index and the city page and
     * does not depend on which of them asked.
     */
    private function isOnlineRoom(array $venue): bool
    {
        if (($venue['type'] ?? '') !== 'virtual') {
            return false;
        }

        $first = (new VenueModel())->live()->where('type', 'virtual')->first();

        return $first !== null && (int) $first['id'] === (int) $venue['id'];
    }

    /**
     * Add the next date and the number of dates to each venue, in two queries
     * rather than two per venue.
     *
     * @param  list<array> $venues
     * @return list<array>
     */
    private function summarise(array $venues): array
    {
        if ($venues === []) {
            return [];
        }

        $db      = db_connect();
        $ids     = array_map('intval', array_column($venues, 'id'));
        $roomId  = null;

        foreach ($venues as $venue) {
            if ($venue['type'] === 'virtual') {
                $roomId = (int) $venue['id'];
                break;
            }
        }

        // Dates that name a venue, grouped.
        $placed = $db->table('course_sessions cs')
            ->select('cs.venue_id, MIN(cs.start_date) AS next_date, COUNT(*) AS date_count', false)
            ->join('courses c', 'c.id = cs.course_id')
            ->whereIn('cs.venue_id', $ids)
            ->where('cs.is_private', 0)
            ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
            ->where('cs.start_date >=', date('Y-m-d'))
            ->where('c.status', 'published')
            ->where('c.deleted_at IS NULL')
            ->groupBy('cs.venue_id')
            ->get()->getResultArray();

        $summary = [];
        foreach ($placed as $row) {
            $summary[(int) $row['venue_id']] = [
                'next_date'  => $row['next_date'],
                'date_count' => (int) $row['date_count'],
            ];
        }

        // And the live online dates that name none, folded into the online
        // room's totals. A second query rather than a CASE expression in the
        // first: the grouping key would have to be rewritten per driver, and
        // this reads as what it is.
        if ($roomId !== null) {
            $unplaced = $db->table('course_sessions cs')
                ->select('MIN(cs.start_date) AS next_date, COUNT(*) AS date_count', false)
                ->join('courses c', 'c.id = cs.course_id')
                ->where('cs.venue_id IS NULL')
                ->where('cs.mode', 'LIVE_ONLINE')
                ->where('cs.is_private', 0)
                ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
                ->where('cs.start_date >=', date('Y-m-d'))
                ->where('c.status', 'published')
                ->where('c.deleted_at IS NULL')
                ->get()->getRowArray();

            if ($unplaced !== null && (int) $unplaced['date_count'] > 0) {
                $held = $summary[$roomId] ?? ['next_date' => null, 'date_count' => 0];

                $summary[$roomId] = [
                    // min() of two date strings is min() of two dates, because
                    // Y-m-d sorts lexically. A null on either side would win a
                    // naive min(), so the empty case is taken first.
                    'next_date'  => $held['next_date'] === null
                        ? $unplaced['next_date']
                        : min($held['next_date'], $unplaced['next_date']),
                    'date_count' => $held['date_count'] + (int) $unplaced['date_count'],
                ];
            }
        }

        foreach ($venues as &$venue) {
            $found               = $summary[(int) $venue['id']] ?? null;
            $venue['next_date']  = $found['next_date'] ?? null;
            $venue['date_count'] = $found['date_count'] ?? 0;
        }

        return $venues;
    }

    /**
     * The dates to list on a city page.
     *
     * @return list<array>
     */
    private function upcomingFor(array $venue, bool $adopts): array
    {
        if (! $adopts) {
            // The model's own method, which is what a classroom needs. It
            // admits a null start_date so that the same query can serve a
            // self-paced listing elsewhere; a dateless row in a table headed
            // "coming up here" and sorted by date sorts anywhere and tells a
            // reader nothing, so it is dropped here rather than in the view.
            return array_values(array_filter(
                (new VenueModel())->upcomingAt((int) $venue['id'], self::UPCOMING),
                static fn (array $row): bool => ! empty($row['start_date'])
            ));
        }

        $builder = db_connect()->table('course_sessions cs')
            ->select('cs.*, c.slug AS course_slug, c.title AS course_title, c.level, c.pillar')
            ->join('courses c', 'c.id = cs.course_id');

        return $this->atVenue($builder, (int) $venue['id'], true)
            ->where('cs.is_private', 0)
            ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
            ->where('cs.start_date >=', date('Y-m-d'))
            ->where('c.status', 'published')
            ->where('c.deleted_at IS NULL')
            ->orderBy('cs.start_date', 'ASC')
            ->orderBy('cs.id', 'ASC')
            ->limit(self::UPCOMING)
            ->get()->getResultArray();
    }

    /**
     * Price every listed date in the visitor's currency, and count the seats
     * for all of them at once.
     *
     * A date with no price in this currency keeps its row. The course page
     * drops those, because it is answering "what does this cost"; this page is
     * answering "when does it run here", and hiding a real class because a
     * price list is incomplete loses a booking to an administrative oversight.
     *
     * @param  list<array> $rows
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

            // Null, never zero. A price of nothing is a free class, which is a
            // different and considerably more expensive thing to publish by
            // accident.
            $row['price_cents']      = $price['price_cents'] ?? null;
            $row['compare_at_cents'] = $price['compare_at_cents'] ?? null;
            $row['seats_left']       = $seatsLeft[(int) $row['id']] ?? null;
            $row['currency']         = $currency;
        }

        return $rows;
    }

    /**
     * The courses this venue actually runs, most often first.
     *
     * Counted over every real date the venue has ever held rather than only the
     * upcoming ones: what a city is known for is a year's pattern, not whichever
     * three classes happen to be on the calendar this fortnight. Draft and
     * cancelled rows are excluded because neither ever happened.
     *
     * @return list<array>
     */
    private function popularCourses(array $venue, bool $adopts, string $currency): array
    {
        $db      = db_connect();
        $venueId = (int) $venue['id'];

        $columns = 'c.id, c.slug, c.title, c.summary, c.level, c.duration_days, c.duration_hours, c.hero_image';

        $builder = $db->table('course_sessions cs')
            ->select($columns . ', COUNT(*) AS runs', false)
            ->join('courses c', 'c.id = cs.course_id');

        $rows = $this->atVenue($builder, $venueId, $adopts)
            ->where('cs.is_private', 0)
            ->whereNotIn('cs.status', ['draft', 'cancelled'])
            ->where('c.status', 'published')
            ->where('c.deleted_at IS NULL')
            // Every non-aggregated column is grouped, because MySQL in
            // ONLY_FULL_GROUP_BY — the default since 5.7 — rejects the query
            // otherwise, and SQLite would have answered it with an arbitrary
            // row instead of an error.
            ->groupBy($columns)
            ->orderBy('runs', 'DESC')
            ->orderBy('c.id', 'ASC')
            ->limit(self::POPULAR)
            ->get()->getResultArray();

        if ($rows === []) {
            return [];
        }

        $ids = array_map('intval', array_column($rows, 'id'));

        // The "from" price and the next date, both scoped to this venue: a card
        // on the Kandy page quoting a Colombo-only date would be a lie about
        // where that class runs.
        $priceQuery = $db->table('course_sessions cs')
            ->select('cs.course_id, MIN(sp.price_cents) AS from_cents', false)
            ->join('session_prices sp', 'sp.session_id = cs.id');

        $prices = array_column(
            $this->atVenue($priceQuery, $venueId, $adopts)
                ->whereIn('cs.course_id', $ids)
                ->where('sp.currency', $currency)
                ->where('cs.is_private', 0)
                ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
                ->groupBy('cs.course_id')
                ->get()->getResultArray(),
            'from_cents',
            'course_id'
        );

        $dateQuery = $db->table('course_sessions cs')
            ->select('cs.course_id, MIN(cs.start_date) AS next_date', false);

        $dates = array_column(
            $this->atVenue($dateQuery, $venueId, $adopts)
                ->whereIn('cs.course_id', $ids)
                ->where('cs.is_private', 0)
                ->whereIn('cs.status', CourseSessionModel::BOOKABLE)
                ->where('cs.start_date >=', date('Y-m-d'))
                ->groupBy('cs.course_id')
                ->get()->getResultArray(),
            'next_date',
            'course_id'
        );

        foreach ($rows as &$row) {
            $row['from_cents'] = isset($prices[$row['id']]) ? (int) $prices[$row['id']] : null;
            $row['next_date']  = $dates[$row['id']] ?? null;
            $row['currency']   = $currency;
        }

        return $rows;
    }

    /**
     * The street address, or null when there is not one fit to print.
     *
     * The seeded venues carry the copy's own placeholder rather than an
     * invented street: a wrong address on a training school's location page
     * sends somebody to the wrong building on the morning of a course they have
     * paid for, and an invented one is worse than none in every direction —
     * on the page, in the JSON-LD, and in whatever a search engine caches from
     * either.
     *
     * Any `{…}` token disqualifies the whole string, not merely its own words.
     * "Level 4, {to be confirmed}" is still an address nobody can arrive at, and
     * printing the half that is filled in makes the page look finished.
     */
    private function publishableAddress(array $venue): ?string
    {
        $address = trim(t_field($venue['address'] ?? ''));

        if ($address === '' || preg_match('/\{[^}]*\}/', $address) === 1) {
            return null;
        }

        return $address;
    }

    /**
     * The venue as a `Place`.
     *
     * Only for a classroom. The online room is a delivery mode wearing a
     * venue's clothes, and a Place with no address, no map and nothing to
     * photograph is a claim that a building exists.
     *
     * `PostalAddress` is emitted only when a field survives the filter. An
     * address object whose members are all empty is not "no address": it is a
     * statement that the address is known and blank, which is the one thing
     * that is definitely untrue here.
     */
    private function place(array $venue, ?string $address): array
    {
        helper(['norlanka', 'url']);

        if (($venue['type'] ?? '') !== 'classroom') {
            return [];
        }

        $url = locale_url('locations/' . $venue['slug']);

        $graph = [
            '@type' => 'Place',
            '@id'   => $url . '#place',
            'name'  => (string) $venue['name'],
            'url'   => $url,
        ];

        $postal = array_filter([
            'streetAddress'   => $address,
            'addressLocality' => trim((string) ($venue['city'] ?? '')) ?: null,
            'addressCountry'  => trim((string) ($venue['country'] ?? '')) ?: null,
        ]);

        if ($postal !== []) {
            $graph['address'] = ['@type' => 'PostalAddress'] + $postal;
        }

        if (! empty($venue['map_url'])) {
            $graph['hasMap'] = (string) $venue['map_url'];
        }

        // Truthful and useful: it is the number of chairs, and it is the reason
        // the classes are small.
        if ((int) ($venue['capacity'] ?? 0) > 0) {
            $graph['maximumAttendeeCapacity'] = (int) $venue['capacity'];
        }

        return $graph;
    }
}
