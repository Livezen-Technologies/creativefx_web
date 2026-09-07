<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * Course reviews.
 *
 * Nothing is ever seeded into this table, and nothing should be. A launch with
 * no reviews shows an empty state that says so; invented testimonials on a
 * commercial site are a lie a customer can act on, and an `AggregateRating` in
 * structured data that no reviewer produced is the same lie told to a search
 * engine.
 *
 * A review is written by somebody who took the class — `enrolment_id` is what
 * stops the reviews page becoming a form anybody on the internet can fill in —
 * and it is moderated before it is published.
 */
class ReviewModel extends Model
{
    protected $table         = 'course_reviews';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'course_id', 'user_id', 'enrolment_id', 'author_name', 'author_role',
        'rating', 'title', 'body', 'source', 'status', 'published_at',
    ];

    /**
     * Columns are table-qualified even though this model has only one table.
     *
     * latest() joins `courses`, which also has `status`, `title` and `id`, so an
     * unqualified `where('status', …)` is an ambiguous column error on the join
     * and works perfectly everywhere else — the kind of bug that appears only on
     * the page that happens to join.
     */
    public function approved(): self
    {
        $this->where('course_reviews.status', 'approved')
            ->orderBy('course_reviews.published_at', 'DESC')
            ->orderBy('course_reviews.id', 'DESC');

        return $this;
    }

    /** @return list<array> */
    public function forCourse(int $courseId, int $limit = 0): array
    {
        return $this->approved()->where('course_reviews.course_id', $courseId)->findAll($limit ?: null);
    }

    /**
     * The most recent approved reviews, across the whole catalogue.
     *
     * Restricted to courses somebody can actually open. Every one of these is
     * rendered as a link to its course, so a review left on a course that has
     * since been unpublished or deleted was a link to a 404 — on the home page,
     * where it is the first thing a visitor clicks.
     *
     * The window on `published_at` is the same one the reviews index applies:
     * an approved review with a future publication date is scheduled, not live.
     *
     * @return list<array>
     */
    public function latest(int $limit = 9): array
    {
        return $this->liveScope()->findAll($limit);
    }

    /**
     * Approved reviews on live courses.
     *
     * One definition, because the home page and the reviews index were each
     * carrying their own and had already begun to differ: the index filtered on
     * `courses.published_at` and the home page filtered on nothing, so a review
     * could be live on one page and absent from the other with no rule saying
     * which was right.
     *
     * Both dates are checked here, because they answer different questions. A
     * course scheduled to publish next month should not be advertised by a
     * review today; and a review scheduled for next month is not published yet
     * whatever its course is doing.
     *
     * `countAllResults()` resets the builder, so calling this once for the
     * count and once for the slice is safe and is how the index pages.
     */
    public function liveScope(): self
    {
        $now = date('Y-m-d H:i:s');

        $this->approved()
            ->select('course_reviews.*, c.slug AS course_slug, c.title AS course_title')
            ->join('courses c', 'c.id = course_reviews.course_id')
            ->where('c.status', 'published')
            ->where('c.deleted_at IS NULL')
            ->groupStart()
                ->where('c.published_at IS NULL')
                ->orWhere('c.published_at <=', $now)
            ->groupEnd()
            ->groupStart()
                ->where('course_reviews.published_at IS NULL')
                ->orWhere('course_reviews.published_at <=', $now)
            ->groupEnd();

        return $this;
    }

    /**
     * Whether this learner is entitled to review this course: they hold an
     * enrolment on it that has actually started, and they have not reviewed it
     * already.
     */
    public function mayReview(int $userId, int $courseId): bool
    {
        $enrolled = $this->db->table('enrolments')
            ->where('user_id', $userId)->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->countAllResults() > 0;


        if (! $enrolled) {
            return false;
        }

        return $this->where('course_reviews.user_id', $userId)
            ->where('course_reviews.course_id', $courseId)
            ->countAllResults() === 0;
    }
}
