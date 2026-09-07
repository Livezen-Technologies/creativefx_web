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

    /** @return list<array> */
    public function latest(int $limit = 9): array
    {
        return $this->approved()
            ->select('course_reviews.*, c.slug AS course_slug, c.title AS course_title')
            ->join('courses c', 'c.id = course_reviews.course_id')
            ->findAll($limit);
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
