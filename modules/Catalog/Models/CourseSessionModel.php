<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * Sessions — one deliverable instance of a course.
 *
 * `course_sessions`, never `sessions`: CodeIgniter's database session handler
 * owns that name, and "session" means two different things in a web framework
 * and in a training company.
 *
 * Seat counts on these rows are written **only** by
 * `Modules\Commerce\Services\InventoryService`, inside a transaction. Nothing
 * else may touch `seats_sold` or `seats_reserved` — not the admin, not a
 * seeder, not a controller in a hurry. Two people in one chair is the failure
 * mode that ends a training business, and it is entirely a locking problem.
 */
class CourseSessionModel extends Model
{
    protected $table         = 'course_sessions';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'course_id', 'mode', 'venue_id', 'instructor_id', 'language',
        'start_date', 'end_date', 'timezone', 'daily_start', 'daily_end',
        'seats_total', 'seats_reserved', 'seats_sold', 'min_to_run',
        'status', 'cancel_reason', 'meeting_provider', 'meeting_url_enc',
        'notes', 'is_private',
    ];

    /** The statuses a visitor may buy a seat on. */
    public const BOOKABLE = ['open', 'confirmed', 'waitlist'];

    /** The four delivery modes, in the order the site presents them. */
    public const MODES = ['LIVE_ONLINE', 'CLASSROOM', 'SELF_PACED', 'PRIVATE'];

    /**
     * Dates a visitor can see: on sale, not private, and not in the past.
     *
     * Self-paced sessions carry no date at all, so "not in the past" has to
     * admit a null start_date or the whole on-demand library disappears from
     * every listing — which is exactly the bug a naive `start_date >= today`
     * produces, silently, on the day the catalogue goes live.
     */
    public function open(): self
    {
        $this->whereIn('course_sessions.status', self::BOOKABLE)
            ->where('course_sessions.is_private', 0)
            ->groupStart()
                ->where('course_sessions.start_date IS NULL')
                ->orWhere('course_sessions.start_date >=', date('Y-m-d'))
            ->groupEnd();

        return $this;
    }

    /**
     * A course's upcoming dates, soonest first — the conversion engine of the
     * course page. Five to eight real dates with a price and a place is what
     * turns a brochure into a booking.
     *
     * @return list<array>
     */
    public function forCourse(int $courseId, ?string $mode = null, int $limit = 8): array
    {
        return $this->open()
            ->where('course_sessions.course_id', $courseId)
            ->when($mode !== null, static fn ($q) => $q->where('course_sessions.mode', $mode))
            ->orderBy('course_sessions.start_date IS NULL', 'ASC', false)
            ->orderBy('course_sessions.start_date', 'ASC')
            ->findAll($limit ?: null);
    }

    /**
     * The whole schedule, with the course and venue joined on, for /schedule.
     *
     * @param array{mode?:string, city?:string, pillar?:string, from?:string, to?:string, course?:int} $filters
     * @return array{rows:list<array>, total:int}
     */
    public function schedule(array $filters = [], int $perPage = 30, int $page = 1): array
    {
        $build = function () use ($filters) {
            $q = $this->open()
                ->select('course_sessions.*, c.slug AS course_slug, c.title AS course_title, c.pillar, c.level, c.duration_days, v.name AS venue_name, v.city AS venue_city, v.type AS venue_type')
                ->join('courses c', 'c.id = course_sessions.course_id')
                ->join('venues v', 'v.id = course_sessions.venue_id', 'left')
                ->where('c.status', 'published')
                ->where('c.deleted_at IS NULL');

            if (! empty($filters['mode'])) {
                $q->where('course_sessions.mode', $filters['mode']);
            }
            if (! empty($filters['pillar'])) {
                $q->where('c.pillar', $filters['pillar']);
            }
            if (! empty($filters['city'])) {
                $q->where('v.city', $filters['city']);
            }
            if (! empty($filters['course'])) {
                $q->where('course_sessions.course_id', (int) $filters['course']);
            }
            if (! empty($filters['from'])) {
                $q->where('course_sessions.start_date >=', $filters['from']);
            }
            if (! empty($filters['to'])) {
                $q->where('course_sessions.start_date <=', $filters['to']);
            }

            return $q;
        };

        $total = $build()->countAllResults();
        $rows  = $build()
            ->orderBy('course_sessions.start_date IS NULL', 'ASC', false)
            ->orderBy('course_sessions.start_date', 'ASC')
            ->orderBy('course_sessions.id', 'ASC')
            ->findAll($perPage, max(0, ($page - 1) * $perPage));

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * The next few dates across the whole catalogue — the home page's live
     * table, which is the section that most reliably turns a browser into a
     * booking because it proves the school actually runs classes.
     *
     * @return list<array>
     */
    public function upcoming(int $limit = 8): array
    {
        return $this->schedule([], $limit, 1)['rows'];
    }

    /** @return list<array> */
    public function days(int $sessionId): array
    {
        return $this->db->table('session_days')->where('session_id', $sessionId)
            ->orderBy('day_date', 'ASC')->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * One session with everything the booking panel and the Event schema need.
     */
    public function detail(int $id): ?array
    {
        $row = $this->select('course_sessions.*, c.slug AS course_slug, c.title AS course_title, c.summary AS course_summary, c.duration_hours, c.duration_days, c.pillar, v.name AS venue_name, v.city AS venue_city, v.country AS venue_country, v.address AS venue_address, v.type AS venue_type, v.map_url')
            ->join('courses c', 'c.id = course_sessions.course_id')
            ->join('venues v', 'v.id = course_sessions.venue_id', 'left')
            ->where('course_sessions.id', $id)
            ->first();

        if ($row === null) {
            return null;
        }

        $row['days'] = $this->days($id);

        return $row;
    }

    /**
     * Whether a class has enough people booked to be certain of running.
     *
     * Worth being explicit about rather than reading `status` directly: the
     * page says "confirmed to run", which is a promise, and the promise should
     * come from the number rather than from whether somebody remembered to
     * change a dropdown.
     */
    public static function isConfirmed(array $session): bool
    {
        return (int) $session['seats_sold'] >= max(1, (int) $session['min_to_run']);
    }

    /** Seats left, or null when the session is unlimited (self-paced). */
    public static function seatsLeft(array $session): ?int
    {
        $total = (int) ($session['seats_total'] ?? 0);
        if ($total === 0) {
            return null;
        }

        return max(0, $total - (int) $session['seats_sold'] - (int) $session['seats_reserved']);
    }
}
