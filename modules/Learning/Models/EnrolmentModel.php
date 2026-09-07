<?php

namespace Modules\Learning\Models;

use CodeIgniter\Model;

/**
 * A learner's place on a course.
 *
 * Created only by EnrolmentService, from a payment that was verified. Nothing
 * creates an enrolment because somebody reached a URL, and every gated thing on
 * this site — the joining link, the recording, the materials, the certificate,
 * the right to leave a review — is a question about whether a row exists here.
 */
class EnrolmentModel extends Model
{
    protected $table         = 'enrolments';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'order_item_id', 'course_id', 'session_id', 'bundle_id',
        'account_id', 'mode', 'status', 'source', 'enrolled_at', 'completed_at',
        'expires_at',
    ];

    /**
     * Everything one learner is entitled to, with the course and dates joined
     * on — the query the account dashboard is built from.
     *
     * @return list<array>
     */
    public function forUser(int $userId, array $statuses = ['active', 'completed']): array
    {
        return $this->select('enrolments.*, c.slug AS course_slug, c.title AS course_title, c.summary AS course_summary, c.hero_image, c.duration_days, c.duration_hours, c.pillar, cs.start_date, cs.end_date, cs.timezone, cs.daily_start, cs.daily_end, cs.status AS session_status, cs.mode AS session_mode, v.name AS venue_name, v.city AS venue_city')
            ->join('courses c', 'c.id = enrolments.course_id', 'left')
            ->join('course_sessions cs', 'cs.id = enrolments.session_id', 'left')
            ->join('venues v', 'v.id = cs.venue_id', 'left')
            ->where('enrolments.user_id', $userId)
            ->whereIn('enrolments.status', $statuses)
            ->orderBy('cs.start_date IS NULL', 'ASC', false)
            ->orderBy('cs.start_date', 'ASC')
            ->orderBy('enrolments.id', 'DESC')
            ->findAll();
    }

    /**
     * The classes this learner is booked onto that have not happened yet.
     *
     * A session with no start date is self-paced study, which is available now
     * rather than upcoming, so it belongs in the other list. Getting this the
     * wrong way round puts the entire on-demand library into "what's coming up",
     * where it never goes away.
     *
     * @return list<array>
     */
    public function upcomingFor(int $userId): array
    {
        return array_values(array_filter(
            $this->forUser($userId, ['active']),
            static fn (array $e): bool => ! empty($e['start_date']) && $e['start_date'] >= date('Y-m-d')
        ));
    }

    /** @return list<array> */
    public function selfPacedFor(int $userId): array
    {
        return array_values(array_filter(
            $this->forUser($userId, ['active', 'completed']),
            static fn (array $e): bool => empty($e['start_date'])
        ));
    }

    /**
     * The enrolment that lets this learner into this course's lessons, or null.
     *
     * The ordering is the point of this method. One learner can hold two
     * enrolments on one course — a taught seat from a class they sat, and a
     * self-paced licence bought afterwards — and something has to say which of
     * them a lesson completed in the player belongs to. Taking the oldest, as
     * the player used to, gives every quiz attempt and every completion to the
     * taught seat; CertificateService then judges a self-paced learner by their
     * attendance at a classroom session and refuses a certificate they earned.
     *
     * The player *is* the self-paced route — a taught learner's certificate
     * comes from the register the instructor marks, not from clicking through
     * lessons — so a self-paced enrolment wins. Between two of a kind the most
     * recent wins, that being the one still being paid for.
     */
    public function accessRowFor(int $userId, int $courseId): ?array
    {
        return $this->select('enrolments.*')
            ->join('course_sessions cs', 'cs.id = enrolments.session_id', 'left')
            ->where('enrolments.user_id', $userId)
            ->where('enrolments.course_id', $courseId)
            ->whereIn('enrolments.status', ['active', 'completed'])
            // A self-paced licence runs for twelve months and the course page
            // says so. A null expiry is a taught seat, which never lapses —
            // written as two clauses rather than one because a plain
            // `expires_at >= now` would silently take the LMS away from every
            // classroom learner, whose expiry is null and always will be.
            ->groupStart()
                ->where('enrolments.expires_at IS NULL')
                ->orWhere('enrolments.expires_at >=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->orderBy("CASE WHEN enrolments.session_id IS NULL OR cs.mode = 'SELF_PACED' THEN 0 ELSE 1 END", 'ASC', false)
            ->orderBy('enrolments.id', 'DESC')
            ->first();
    }

    /**
     * Whether this learner may open this course's lessons.
     *
     * Defined in terms of accessRowFor() so that the row which grants access
     * and the row the learner's work is recorded against cannot drift apart.
     */
    public function hasAccess(int $userId, int $courseId): bool
    {
        return $this->accessRowFor($userId, $courseId) !== null;
    }

    /**
     * The register for one session — who is coming, so an instructor can mark
     * attendance and an administrator can email the room.
     *
     * @return list<array>
     */
    public function roster(int $sessionId): array
    {
        return $this->select('enrolments.*, u.first_name, u.last_name, u.email, u.phone, u.company')
            ->join('users u', 'u.id = enrolments.user_id')
            ->where('enrolments.session_id', $sessionId)
            ->whereIn('enrolments.status', ['active', 'completed'])
            ->orderBy('u.last_name', 'ASC')
            ->findAll();
    }
}
