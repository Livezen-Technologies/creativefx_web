<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeImmutable;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\CourseModel;
use Modules\Catalog\Models\WebinarModel;

/**
 * The free live sessions.
 *
 * A webinar is the cheapest introduction a training school can make: an hour,
 * free, with the person who would teach the paid course standing in front of
 * you. It answers "are these people any good" in a way no page can, and the
 * recording afterwards goes on answering it for years.
 *
 * The controller exists to make three judgements the views should not.
 *
 * **Upcoming and past are different products and are presented as such.** An
 * upcoming webinar sells a registration; a past one sells a recording. They are
 * split before they reach the template rather than sorted into one list with a
 * date test in the loop.
 *
 * **A past webinar without a recording is not listed at all.** It is an old
 * date and a dead end, and a grid of them advertises a school that does not
 * record its sessions. Its own page stays reachable — the address may be in
 * somebody's calendar invitation, and a 404 there is worse than a page that
 * says the session has happened — but nothing on the site points at it.
 *
 * **Times are shown in the webinar's own zone, with the zone named.** A live
 * session sold across Colombo, Dubai and London has three clocks around it, and
 * a page that prints one of them without saying which is a page somebody joins
 * an hour late. The machine-readable `datetime` attribute carries the offset so
 * the browser and the crawler have no guessing to do either.
 */
class Webinars extends BaseController
{
    // ── Coming up, and worth catching up on ─────────────────────────────────

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $webinars = new WebinarModel();

        $upcoming = array_map([$this, 'present'], $webinars->upcoming());

        // The model asks whether a recording column holds anything; `present()`
        // asks whether what it holds is a link a browser will follow. Both
        // questions have to be answered before a card can promise a recording,
        // and they are answered in different places — so the second filter is
        // here rather than folded into the query. A `javascript:` value typed
        // into the admin would otherwise pass the model's test, be dropped by
        // safeUrl(), and leave a card in "Past sessions" saying "Watch the
        // recording" over nothing at all.
        $past = array_values(array_filter(
            array_map([$this, 'present'], $webinars->past(12)),
            static fn (array $row): bool => $row['recording_url'] !== null
        ));

        $crumbs = [['label' => lang('Catalog.webinars.title')]];

        return view('Modules\Catalog\Views\webinars\index', [
            'upcoming'        => $upcoming,
            'past'            => $past,
            'crumbs'          => $crumbs,
            // Each upcoming session is an Event in its own right, so the
            // listing carries the same graphs its detail pages do. Nothing is
            // emitted for the recordings: `Event` describes something that is
            // going to happen, and marking a finished session as scheduled is
            // how a site ends up in a search result advertising last March.
            'schema'          => Schema::render(array_merge(
                [Schema::organisation()],
                array_map(fn (array $row): array => $this->eventGraph($row), $upcoming),
                [Schema::breadcrumbs($crumbs)]
            )),
            'title'           => lang('Catalog.webinars.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.webinars.meta'),
            'canonical'       => locale_url('webinars'),
        ]);
    }

    // ── One session ─────────────────────────────────────────────────────────

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $webinar = (new WebinarModel())->findLive((string) $slug);
        if ($webinar === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $webinar = $this->present($webinar);

        // The course this session was drawn from, when it is one somebody can
        // actually book. `findLive()` on the id rather than `find()`: the
        // pointer survives the course being unpublished, and a link from a page
        // built to convert to a 404 is worse than no link.
        $course = null;
        if (! empty($webinar['course_id'])) {
            $candidate = (new CourseModel())->live()->where('courses.id', (int) $webinar['course_id'])->first();
            $course    = $candidate ?: null;
        }

        $crumbs = [
            ['label' => lang('Catalog.webinars.title'), 'url' => locale_url('webinars')],
            ['label' => t_field($webinar['title'])],
        ];

        return view('Modules\Catalog\Views\webinars\show', [
            'webinar'         => $webinar,
            'course'          => $course,
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([
                Schema::organisation(),
                $this->eventGraph($webinar),
                Schema::breadcrumbs($crumbs),
            ]),
            'title'           => t_field($webinar['seo_title']) ?: (t_field($webinar['title']) . ' — ' . setting('site_name', '')),
            'metaDescription' => t_field($webinar['seo_description']) ?: t_field($webinar['summary']),
            'metaKeywords'    => (string) $webinar['seo_keywords'],
            'ogImage'         => ! empty($webinar['hero_image']) ? media_src($webinar['hero_image']) : null,
            'canonical'       => locale_url('webinars/' . $webinar['slug']),
            'lastUpdated'     => $webinar['updated_at'] ?? null,
        ]);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * A row, with everything the templates would otherwise have to work out.
     *
     * The date arithmetic is done once here rather than in two views and a
     * card, because it is the same arithmetic each time and it is the kind that
     * goes wrong quietly: a zone read from the wrong column, an end time
     * computed from today rather than from the session's own date, an offset
     * taken in July for a class in October.
     *
     * @return array the row plus when_iso, when_end_iso, when_date, when_short,
     *               when_time, zone_abbr, zone_name, is_past, register_url,
     *               recording_url
     */
    private function present(array $webinar): array
    {
        $start = WebinarModel::startsAt($webinar);
        $end   = WebinarModel::endsAt($webinar);
        $zone  = WebinarModel::zoneOf($webinar);

        $webinar['zone_name'] = str_replace('_', ' ', $zone);
        $webinar['is_past']   = WebinarModel::hasEnded($webinar);
        // Rewritten in place, so nothing downstream can reach the raw column
        // and put an unchecked href on the page. See safeUrl().
        $webinar['register_url']  = $this->safeUrl($webinar['register_url'] ?? null);
        $webinar['recording_url'] = $this->safeUrl($webinar['recording_url'] ?? null);

        if ($start === null || $end === null) {
            // A published webinar with no date is a real state — the school
            // intends to run it and has not fixed the day. Everything
            // time-shaped is null rather than defaulted to now, so a template
            // that forgets to check prints nothing rather than today's date.
            return $webinar + [
                'when_iso'     => null,
                'when_end_iso' => null,
                'when_date'    => null,
                'when_short'   => null,
                'when_time'    => null,
                'zone_abbr'    => null,
            ];
        }

        return $webinar + [
            // Full ISO 8601 with the offset, for <time datetime> and for the
            // Event graph. This is the only unambiguous form on the page.
            'when_iso'     => $start->format('c'),
            'when_end_iso' => $end->format('c'),
            'when_date'    => $start->format('l j F Y'),
            // The short form is for a card, where the weekday and the full
            // month name push the line onto three rows on a phone.
            'when_short'   => $start->format('j M Y'),
            'when_time'    => $start->format('H:i') . '–' . $end->format('H:i'),
            // 'T' gives the zone abbreviation where one exists and the numeric
            // offset where it does not, which is why the IANA name is printed
            // beside it: "+0530" alone tells a reader nothing about which city
            // the clock belongs to.
            'zone_abbr'    => $start->format('T'),
        ];
    }

    /**
     * An external link, or null.
     *
     * Both URL columns are free-text fields in the admin, and a value typed
     * there is rendered as an `href` on a public page. `javascript:` in one is
     * stored cross-site scripting; a relative path is a broken link dressed up
     * as a registration page. Anything that is not plainly http(s) is dropped,
     * and the templates treat null as "no link", which they already have to
     * handle for a session with no recording yet.
     */
    private function safeUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || preg_match('~^https?://~i', $url) !== 1) {
            return null;
        }

        return $url;
    }

    /**
     * One upcoming session, as an `Event`.
     *
     * Built here rather than added to `Modules\Catalog\Libraries\Schema`,
     * which is shared with the course and session pages and belongs to nobody
     * in particular this week. Nothing about the shape is unusual enough to
     * justify editing it from here.
     *
     * Two fields carry the meaning. `isAccessibleForFree` is what stops Google
     * treating an event with no offer as one whose price it could not find, and
     * the zero-priced `Offer` says the same thing in the form the rich result
     * actually reads. Both are true: these sessions are free, and neither is
     * emitted for anything else.
     *
     * Returns an empty array for a session that has finished or has no date;
     * `Schema::render()` drops empties, so the callers need no test of their
     * own. An `Event` for something that has already happened is a search
     * result advertising a date in the past.
     */
    private function eventGraph(array $webinar): array
    {
        helper(['norlanka', 'commerce', 'url']);

        if ($webinar['when_iso'] === null || ! empty($webinar['is_past'])) {
            return [];
        }

        $url = locale_url('webinars/' . $webinar['slug']);

        return [
            '@type'               => 'Event',
            '@id'                 => $url . '#event',
            'name'                => t_field($webinar['title']),
            'description'         => t_field($webinar['summary']) ?: strip_tags(t_field($webinar['body'] ?? '')),
            'startDate'           => $webinar['when_iso'],
            'endDate'             => $webinar['when_end_iso'],
            'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
            'eventStatus'         => 'https://schema.org/EventScheduled',
            'organizer'           => ['@id' => rtrim(base_url(), '/') . '/#organisation'],
            'url'                 => $url,
            'isAccessibleForFree' => true,
            // The registration page when there is one, and this page when there
            // is not, because a VirtualLocation still has to point somewhere a
            // person can go.
            'location'            => [
                '@type' => 'VirtualLocation',
                'url'   => $webinar['register_url'] ?? $url,
            ],
            'offers'              => [
                '@type'         => 'Offer',
                'price'         => '0',
                // Required even at zero. The currency is the visitor's own, so
                // the graph says the same thing the page does.
                'priceCurrency' => current_currency(),
                'availability'  => 'https://schema.org/InStock',
                'url'           => $webinar['register_url'] ?? $url,
                'validFrom'     => (new DateTimeImmutable())->format('c'),
            ],
        ];
    }
}
