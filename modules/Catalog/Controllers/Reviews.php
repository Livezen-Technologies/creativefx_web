<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\ReviewModel;
use Modules\Learning\Models\EnrolmentModel;

/**
 * Every review the school has, and the one door through which one is written.
 *
 * The page exists so that the honesty rule has somewhere to be stated out loud
 * rather than merely obeyed. Nothing is ever seeded into `course_reviews`, so
 * at launch this page is empty — and an empty reviews page is the most useful
 * page on a new commercial training site provided it says why it is empty. A
 * visitor who reads that the school is new and that we would rather show
 * nothing than something we wrote ourselves has been told something true about
 * how the rest of the site was written, which is worth more than five
 * testimonials would have been.
 *
 * Two rules govern the write side, and both live here rather than in the form:
 *
 *   1. **A review comes from somebody who took the course.**
 *      `ReviewModel::mayReview()` is the whole of that test — an active or
 *      completed enrolment, and no review from this learner on this course
 *      already — and it is asked again on the POST. A form rendered for one
 *      person is not a permission held by whoever posts to the address.
 *   2. **A review is read by a human before it appears.** It is stored
 *      `pending` with no `published_at`, and nothing here calls
 *      `CourseModel::refreshRating()`: a pending row is not part of the average
 *      by definition, so the recount belongs to the moment a moderator
 *      approves it, in the admin.
 *
 * There is deliberately no `AggregateRating` in the head. The site publishes
 * that only where genuine reviews exist, per course, and a school-wide average
 * asserted on a page that may hold nothing is precisely the claim this page was
 * built to refuse.
 */
class Reviews extends BaseController
{
    /** Reviews per page. */
    private const PER_PAGE = 20;

    /**
     * Submissions allowed per learner, and per address, in an hour.
     *
     * `mayReview()` already caps a learner at one review per course, so this is
     * not what stops a flood of reviews — it is the cost attached to a
     * *rejected* attempt, so that posting course ids at the endpoint with a
     * borrowed session is not free. The address limit is the wider of the two
     * because an office or a mobile carrier is one address to us and many
     * people to itself.
     */
    private const PER_USER = 6;
    private const PER_IP   = 20;

    // ── The page ────────────────────────────────────────────────────────────

    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $model = new ReviewModel();

        // Counted first, so the page number can be clamped before the slice is
        // asked for. Without it a hand-typed ?page=99 renders an empty grid,
        // and the empty grid says "no reviews have been published yet" — which
        // on a site that has some is the one page here that would be lying.
        $total = $model->liveScope()->countAllResults();
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page  = min(max(1, (int) $this->request->getGet('page')), $pages);

        $rows = $total === 0
            ? []
            : $model->liveScope()->findAll(self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        $userId = LearnerAuth::id();
        $crumbs = [['label' => lang('Catalog.reviews.title')]];

        return view('Modules\Catalog\Views\reviews\index', [
            'reviews'         => $rows,
            'total'           => $total,
            'page'            => $page,
            'perPage'         => self::PER_PAGE,
            'reviewable'      => $this->reviewable($userId),
            'signedIn'        => $userId !== null,
            'errors'          => session()->getFlashdata('errors') ?? [],
            'crumbs'          => $crumbs,
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => lang('Catalog.reviews.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Catalog.reviews.meta'),
            // Built rather than left to the layout's current_url(), which drops
            // the query string and would declare page one canonical for every
            // page of the list.
            'canonical'       => locale_url('reviews') . ($page > 1 ? '?page=' . $page : ''),
            // The newest review's date, or none at all. A page that has never
            // had anything on it shows no "last updated" stamp, because an
            // automatic date that is not the truth is worse than no date — and
            // on this page of all pages.
            'lastUpdated'     => $rows === [] ? null : ($rows[0]['published_at'] ?? null),
        ]);
    }

    // ── The submission ──────────────────────────────────────────────────────

    /**
     * A review, from somebody who took the course.
     *
     * Posted to the same address the page is served from, so a submission that
     * fails validation comes back to the form it was written in with the words
     * still in it.
     *
     * Everything it refuses, it refuses with one sentence. A visitor who is not
     * signed in, one who never enrolled and one who has already reviewed this
     * course are all told that reviews come from people who took the course:
     * three different answers would turn the endpoint into a way of asking
     * whether a given account has bought a given course, which for a training
     * school is the customer list one row at a time.
     */
    public function submit(?string $locale = null): RedirectResponse
    {
        helper(['norlanka', 'catalog', 'url']);

        $back   = locale_url('reviews');
        $userId = LearnerAuth::id();

        // Throttled above the entitlement check on purpose. Checking cheaply
        // first and charging only for the submissions that succeed would make
        // the rejected path the fast one, and the rejected path is the one an
        // abusive client is on.
        $throttle = service('throttler');
        $allowed  = $throttle->check(md5('review-ip-' . $this->request->getIPAddress()), self::PER_IP, HOUR)
            && ($userId === null || $throttle->check(md5('review-' . $userId), self::PER_USER, HOUR));

        if (! $allowed) {
            return redirect()->to($back . '#leave')->withInput()->with('review_error', lang('Reviews.throttled'));
        }

        $courseId = (int) $this->request->getPost('course_id');
        $reviews  = new ReviewModel();

        // The posted course id is never trusted: the form carries it as a
        // hidden field because the page renders one form per course, and the
        // only thing that makes it legitimate is this call.
        if ($userId === null || $courseId <= 0 || ! $reviews->mayReview($userId, $courseId)) {
            return redirect()->to($back . '#leave')->with('review_error', lang('Catalog.reviews.blocked'));
        }

        if (! $this->validate([
            // Bounded at both ends. The column would take a 9 without
            // complaining, and an out-of-range row does not merely draw oddly:
            // it lands in the average CourseModel::refreshRating() publishes as
            // the course's star rating on every page that shows one.
            'rating'      => 'required|is_natural_no_zero|less_than[6]',
            'title'       => 'permit_empty|max_length[191]',
            // A floor as well as a ceiling. Two words is not a review, and a
            // moderator rejecting "good" by hand every day is a queue nobody
            // keeps up with.
            'body'        => 'required|min_length[30]|max_length[5000]',
            'author_name' => 'permit_empty|max_length[128]',
        ])) {
            return redirect()->to($back . '#leave')
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // The name to publish it under, falling back to the name on the account
        // — but never to the email address. `LearnerAuth::displayName()`
        // returns the email when no name has been set, so without this test the
        // first learner who registered without a name has their address printed
        // on a public page, permanently, by a form that never asked them.
        $name = trim((string) $this->request->getPost('author_name'));
        if ($name === '') {
            $name = trim((string) (LearnerAuth::user()['name'] ?? ''));
        }
        if ($name === '' || str_contains($name, '@')) {
            $name = null;
        }

        // The enrolment the review is written against. Stored because it is the
        // evidence for the review rather than a convenience: a moderator
        // reading the row a year from now needs to see which booking produced
        // it. The newest is taken where a learner has sat the course twice,
        // that being the class they have just been to.
        $enrolment = (new EnrolmentModel())
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->orderBy('id', 'DESC')
            ->first();

        $reviews->insert([
            'course_id'    => $courseId,
            'user_id'      => $userId,
            'enrolment_id' => $enrolment === null ? null : (int) $enrolment['id'],
            'author_name'  => $name,
            'rating'       => (int) $this->request->getPost('rating'),
            'title'        => trim((string) $this->request->getPost('title')) ?: null,
            'body'         => trim((string) $this->request->getPost('body')),
            'source'       => 'site',
            'status'       => 'pending',
            // Left empty until a moderator approves it. The listing orders on
            // this column, so a row that dated itself here would sort by the
            // moment it was written rather than the moment it was published.
            'published_at' => null,
        ]);

        // CourseModel::refreshRating() is deliberately not called. A pending
        // review is not part of the average, so the recount would produce the
        // number the course already carries — a write on a public POST for
        // nothing. The real recount happens on approval, in the admin.
        return redirect()->to($back)->with('review_ok', lang('Catalog.reviews.thanks'));
    }

    // ── Internals ───────────────────────────────────────────────────────────


    /**
     * The courses this learner may review, in the order they took them.
     *
     * The page asks one thing of an enrolment that the endpoint does not: the
     * class has to have started. `mayReview()` accepts an active enrolment on a
     * course that runs next month, which is correct as an entitlement — the
     * seat is bought and the row exists — but inviting somebody to review a
     * class they have not yet attended produces a review worth nothing to the
     * next reader and a row a moderator has to reject by hand. So the offer is
     * narrower than the rule, and the rule is still what the POST enforces.
     *
     * Runs over the learner's own enrolments — a handful — rather than over the
     * catalogue, because `mayReview()` is two queries each time it is asked.
     *
     * @return list<array{id:int, slug:string, title:string}>
     */
    private function reviewable(?int $userId): array
    {
        if ($userId === null) {
            return [];
        }

        $reviews = new ReviewModel();
        $today   = date('Y-m-d');
        $checked = [];
        $out     = [];

        foreach ((new EnrolmentModel())->forUser($userId) as $enrolment) {
            $courseId = (int) ($enrolment['course_id'] ?? 0);

            // A bundle enrolment carries no course of its own; its member
            // courses arrive as rows of their own.
            if ($courseId === 0 || empty($enrolment['course_slug']) || isset($checked[$courseId])) {
                continue;
            }

            // An empty start date is self-paced study, which began the moment
            // it was bought. A dated class that has not started is skipped
            // *without* being marked as checked: the same course may appear
            // again further down the list on an earlier booking, and marking it
            // here would hide a course the learner has genuinely sat.
            if (! empty($enrolment['start_date']) && $enrolment['start_date'] > $today) {
                continue;
            }

            $checked[$courseId] = true;

            if (! $reviews->mayReview($userId, $courseId)) {
                continue;
            }

            $out[] = [
                'id'    => $courseId,
                'slug'  => (string) $enrolment['course_slug'],
                'title' => t_field($enrolment['course_title']),
            ];
        }

        return $out;
    }
}
