<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Catalog\Models\CourseModel;
use Modules\Catalog\Models\ReviewModel;

/**
 * The review moderation queue.
 *
 * There is no create form and no edit form here, and that absence is the whole
 * design. A review is somebody else's words about a class they sat in; the only
 * decisions this screen offers are to publish them, to refuse them, or to
 * remove them. Nothing on this site writes a review — `course_reviews` is never
 * seeded, and the public form checks for an enrolment before it accepts one —
 * because an invented testimonial on a commercial site is a lie a customer can
 * act on, and an `AggregateRating` in structured data that no reviewer produced
 * is the same lie told to a search engine.
 *
 * **Every path through this controller ends in `CourseModel::refreshRating()`.**
 * `courses.rating_avg` and `courses.rating_count` are a cache of the approved
 * rows, and they are read by the course page, the cards, the catalogue sort and
 * the JSON-LD. Approving without refreshing leaves a five-star review invisible;
 * rejecting or deleting without refreshing leaves a withdrawn review still
 * counting towards an average and a count the school then publishes to Google.
 * The second of those is the one that matters, so the refresh is not attached to
 * "approve" — it is attached to every write, including the delete, where the
 * course id has to be read before the row goes.
 */
class Reviews extends BaseController
{
    /** The three states a review can be in. Also the queue's tabs. */
    public const STATUSES = ['pending', 'approved', 'rejected'];

    private const PER_PAGE = 50;

    public function index()
    {
        helper('norlanka');

        $status = (string) $this->request->getGet('status');
        if (! in_array($status, self::STATUSES, true)) {
            $status = '';
        }

        $counts = $this->countsByStatus();
        $total  = $status === '' ? array_sum($counts) : ($counts[$status] ?? 0);
        $pages  = max(1, (int) ceil($total / self::PER_PAGE));
        $page   = min(max(1, (int) $this->request->getGet('page')), $pages);

        // Every column is table-qualified. `courses` carries its own `status`,
        // `title` and `id`, so an unqualified where or order here is an
        // ambiguous-column error the moment the join is added — and it works
        // perfectly on every screen that does not join.
        $query = (new ReviewModel())
            ->select('course_reviews.*, c.title AS course_title, c.slug AS course_slug')
            ->select('u.email AS user_email, u.first_name, u.last_name')
            ->select('cs.start_date AS attended_on')
            ->join('courses c', 'c.id = course_reviews.course_id', 'left')
            ->join('users u', 'u.id = course_reviews.user_id', 'left')
            ->join('enrolments e', 'e.id = course_reviews.enrolment_id', 'left')
            ->join('course_sessions cs', 'cs.id = e.session_id', 'left');

        if ($status !== '') {
            $query->where('course_reviews.status', $status);
        }

        $rows = $query
            // Waiting first, whatever else is on the screen: a moderation queue
            // that buries the four things needing a decision under two hundred
            // already-approved ones is a queue nobody works. The comparison
            // yields 1/0 on both drivers this site runs on, so it needs no
            // engine-specific CASE.
            ->orderBy("course_reviews.status = 'pending'", 'DESC', false)
            ->orderBy('course_reviews.id', 'DESC')
            ->findAll(self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        return view('Modules\Admin\Views\reviews\index', [
            'title'    => 'Reviews',
            'active'   => 'reviews',
            'rows'     => $rows,
            'counts'   => $counts,
            'statuses' => self::STATUSES,
            'current'  => $status,
            'page'     => $page,
            'pages'    => $pages,
            'total'    => $total,
        ]);
    }

    /**
     * Publish a review, refuse it, or put it back in the queue.
     *
     * A rejected review is kept rather than deleted: it is the record of a
     * complaint somebody made, and a school that quietly removes the ones it
     * dislikes should at least have to look at them. Delete is for spam.
     */
    public function update($id)
    {
        $reviews = new ReviewModel();
        $row     = $reviews->find((int) $id);

        if ($row === null) {
            return redirect()->to(site_url('admin/reviews'))->with('error', 'That review no longer exists.');
        }

        $status = (string) $this->request->getPost('status');
        if (! in_array($status, self::STATUSES, true)) {
            return redirect()->back()->with('error', 'That is not a state a review can be in.');
        }

        $reviews->update((int) $row['id'], [
            'status' => $status,
            // Set once, on the first approval, and never restamped. A review
            // that was pulled and later put back should return to where it sat
            // in the list rather than reappear at the top as though it were
            // written this morning.
            'published_at' => $status === 'approved'
                ? ($row['published_at'] ?: date('Y-m-d H:i:s'))
                : $row['published_at'],
        ]);

        // After the write, always — including on a rejection. See the class
        // comment: a withdrawn review that still counts is the failure worth
        // designing against.
        (new CourseModel())->refreshRating((int) $row['course_id']);

        $admin = session()->get('admin_user') ?? [];
        log_message('info', 'Review {id} on course {course} set to {status} by {who}', [
            'id'     => (int) $row['id'],
            'course' => (int) $row['course_id'],
            'status' => $status,
            'who'    => (string) ($admin['email'] ?? 'unknown'),
        ]);

        return redirect()->back()->with('message', match ($status) {
            'approved' => 'Review published. The course average has been recalculated.',
            'rejected' => 'Review rejected. It is no longer counted in the course average.',
            default    => 'Review put back in the queue.',
        });
    }

    /** Remove a review outright — spam, or a duplicate. */
    public function delete($id)
    {
        $reviews = new ReviewModel();
        $row     = $reviews->find((int) $id);

        if ($row === null) {
            return redirect()->to(site_url('admin/reviews'))->with('error', 'That review no longer exists.');
        }

        // Read before the delete. After it there is no row to ask which course
        // needs recalculating, and the average would keep the deleted rating in
        // it until something else happened to touch that course.
        $courseId = (int) $row['course_id'];

        $reviews->delete((int) $row['id']);
        (new CourseModel())->refreshRating($courseId);

        return redirect()->back()->with('message', 'Review deleted. The course average has been recalculated.');
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /** @return array<string, int> */
    private function countsByStatus(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);

        $rows = db_connect()->table('course_reviews')
            ->select('status, COUNT(*) AS n', false)
            ->groupBy('status')
            ->get()->getResultArray();

        foreach ($rows as $row) {
            $status          = (string) $row['status'];
            $counts[$status] = ($counts[$status] ?? 0) + (int) $row['n'];
        }

        return $counts;
    }
}
