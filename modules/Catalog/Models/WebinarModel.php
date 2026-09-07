<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Free live sessions.
 *
 * Deliberately not `course_sessions`. A session is a seat somebody buys, with
 * inventory, a price and a transactional hold behind it; a webinar is free, has
 * no seat limit worth enforcing, and is usually registered for and hosted
 * somewhere else entirely. Pushing one through the commerce pipeline would mean
 * a zero-priced order, an enrolment and an invoice for every registration.
 *
 * The listing splits on one question — has it happened — and the answer is less
 * obvious than it looks, which is what most of this class is about.
 *
 * **`starts_at` is a wall clock, not an instant.** It is stored as the local
 * time in the row's own `timezone`, exactly as `course_sessions.start_date` and
 * `daily_start` are, and the admin form says so. So `WHERE starts_at >= NOW()`
 * is wrong: it silently reads every row as if it were in the server's zone, and
 * a Dubai webinar at 09:00 and a Colombo one at 09:00 are ninety minutes apart.
 * The SQL here therefore only narrows the set to a generous window, and the
 * real comparison is made per row against that row's own zone, in PHP.
 *
 * **A webinar in progress is upcoming, not past.** The boundary is when it
 * *ends*, not when it starts. On the naive test a session drops out of "coming
 * up" the moment it begins and — having no recording yet — disappears from the
 * site entirely, in the hour when people are looking for the link.
 *
 * `upcoming()` and `past()` both decide with `hasEnded()`, so the two lists
 * cannot overlap and cannot leave a gap between them.
 */
class WebinarModel extends Model
{
    protected $table         = 'webinars';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'title', 'summary', 'body', 'starts_at', 'duration_min',
        'timezone', 'register_url', 'recording_url', 'hero_image', 'course_id',
        'seo_title', 'seo_description', 'seo_keywords', 'status',
    ];

    /**
     * How far either side of now the SQL window reaches.
     *
     * It has to cover the whole spread of world timezones — UTC-12 to UTC+14 is
     * twenty-six hours — plus the longest webinar anybody would schedule, or a
     * row the exact test would have kept is excluded before PHP ever sees it.
     * Two days is comfortably past both and still narrows the table to a
     * handful of rows.
     */
    private const WINDOW_HOURS = 48;

    /** The fallback zone, matching the one the migration defaults to. */
    private const ZONE = 'Asia/Colombo';

    /** Published, newest first — the order the admin and the sitemap want. */
    public function live(): self
    {
        $this->where('status', 'published')
            ->orderBy('starts_at', 'DESC')
            ->orderBy('id', 'DESC');

        return $this;
    }

    public function findLive(string $slug): ?array
    {
        return $this->live()->where('slug', $slug)->first();
    }

    /**
     * Still to happen, soonest first.
     *
     * A row with no date at all is included and sorted last. A published
     * webinar without a date means the school intends to run it and has not
     * fixed the day, which is a real state the admin allows; dropping it would
     * leave a page in the sitemap that nothing on the site links to.
     *
     * @return list<array>
     */
    public function upcoming(int $limit = 0): array
    {
        $rows = $this->live()
            ->groupStart()
                ->where('starts_at IS NULL')
                ->orWhere('starts_at >=', self::window(-self::WINDOW_HOURS))
            ->groupEnd()
            ->findAll();

        $out = [];
        foreach ($rows as $row) {
            if (! self::hasEnded($row)) {
                $out[] = $row;
            }
        }

        // Sorted here rather than in SQL because the comparison is between
        // instants and the rows are wall clocks in different zones — and
        // because a NULL sorts first in MySQL and last in nothing else, so the
        // undated rows would land at the top of "coming up" on one engine and
        // the bottom on another.
        usort($out, static function (array $a, array $b): int {
            $x = self::startsAt($a);
            $y = self::startsAt($b);

            if ($x === null || $y === null) {
                return ($x === null ? 1 : 0) <=> ($y === null ? 1 : 0);
            }

            return $x->getTimestamp() <=> $y->getTimestamp();
        });

        return $limit > 0 ? array_slice($out, 0, $limit) : $out;
    }

    /**
     * Finished, and worth keeping — most recent first.
     *
     * Only the ones with a recording. A past webinar with a recording is an
     * asset that goes on earning: it ranks, it is watched, and it sells the
     * course it was drawn from. One without is an old date and a dead page, and
     * listing it advertises that the school does not record its sessions.
     *
     * @return list<array>
     */
    public function past(int $limit = 0): array
    {
        $rows = $this->live()
            ->where('starts_at IS NOT NULL')
            ->where('starts_at <=', self::window(self::WINDOW_HOURS))
            ->where('recording_url IS NOT NULL')
            ->findAll();

        $out = [];
        foreach ($rows as $row) {
            // The emptiness test is in PHP rather than in the query: a column
            // cleared through a text input holds '' rather than NULL, and
            // `recording_url IS NOT NULL` happily returns it.
            if (trim((string) $row['recording_url']) !== '' && self::hasEnded($row)) {
                $out[] = $row;
            }
        }

        usort($out, static fn (array $a, array $b): int => (self::startsAt($b)?->getTimestamp() ?? 0) <=> (self::startsAt($a)?->getTimestamp() ?? 0));

        return $limit > 0 ? array_slice($out, 0, $limit) : $out;
    }

    /**
     * The start, as a real instant in the webinar's own zone.
     *
     * Null when there is no date, and also when there is one the database
     * accepted and PHP cannot parse: `starts_at` is a free-text field in the
     * admin, and a typo there must produce a page missing its time rather than
     * an uncaught exception on the listing that shows every other webinar too.
     */
    public static function startsAt(array $webinar): ?DateTimeImmutable
    {
        $raw = trim((string) ($webinar['starts_at'] ?? ''));
        if ($raw === '' || str_starts_with($raw, '0000-00-00')) {
            return null;
        }

        try {
            return new DateTimeImmutable($raw, new DateTimeZone(self::zoneOf($webinar)));
        } catch (Throwable) {
            return null;
        }
    }

    /** The end, from the start plus `duration_min`. Null when the start is. */
    public static function endsAt(array $webinar): ?DateTimeImmutable
    {
        $start = self::startsAt($webinar);
        if ($start === null) {
            return null;
        }

        return $start->modify('+' . max(1, (int) ($webinar['duration_min'] ?? 60)) . ' minutes');
    }

    /**
     * Has it finished?
     *
     * An undated webinar has not, which is what puts it in `upcoming()`.
     */
    public static function hasEnded(array $webinar): bool
    {
        $end = self::endsAt($webinar);

        return $end !== null && $end->getTimestamp() < time();
    }

    /** The row's zone, falling back to the school's own rather than the server's. */
    public static function zoneOf(array $webinar): string
    {
        $zone = trim((string) ($webinar['timezone'] ?? ''));
        if ($zone === '') {
            return self::ZONE;
        }

        // Validated rather than trusted: an unrecognised identifier constructs
        // a DateTimeZone that throws, and this value is typed by hand into the
        // admin.
        return in_array($zone, DateTimeZone::listIdentifiers(), true) ? $zone : self::ZONE;
    }

    /** One edge of the SQL window, as the naive string the column holds. */
    private static function window(int $hours): string
    {
        // `appTimezone` is UTC, so date() and the stored wall clocks are read
        // against the same calendar. The window is wide enough that the offset
        // between that and any row's real zone cannot exclude a row the exact
        // test in PHP would have kept.
        return date('Y-m-d H:i:s', time() + ($hours * 3600));
    }
}
