<?php

namespace Modules\Commerce\Controllers;

use App\Controllers\BaseController;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Commerce\Models\MembershipModel;
use Modules\Commerce\Models\MembershipPlanModel;

/**
 * The membership page: four terms, one library.
 *
 * Public on purpose. A visitor has to be able to see what a pass costs before
 * deciding to make an account — putting the price behind a sign-in is how a
 * pricing page stops being a pricing page. The sign-in happens at checkout,
 * where it is needed because a pass has to be given to somebody.
 *
 * What is counted here is counted from the catalogue, never typed. "Thirty-one
 * courses" on a sales page that is actually twenty-nine is the kind of number
 * that is wrong for a year because nobody owns it.
 */
class Membership extends BaseController
{
    public function index(?string $locale = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $currency = current_currency();
        $plans    = (new MembershipPlanModel())->published($currency);

        $userId  = (int) (LearnerAuth::id() ?? 0);
        $current = $userId > 0 ? (new MembershipModel())->activeFor($userId) : null;

        return view('Modules\Commerce\Views\membership\index', [
            'plans'    => $plans,
            'currency' => $currency,
            'current'  => $current,
            // Derived, both of them. See the class comment.
            'courses'  => $this->libraryCount(),
            'hours'    => $this->libraryHours(),
            'crumbs'   => [['label' => lang('Commerce.membership.title')]],
            'title'    => lang('Commerce.membership.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Commerce.membership.meta'),
        ]);
    }

    /**
     * How many courses a pass actually opens.
     *
     * `COUNT(DISTINCT)` rather than the model's `countAllResults()` with a
     * `groupBy`, and that is not a style choice. countAllResults() on a grouped
     * query wraps it in a derived table, and the model's select carries
     * `courses.*` beside a joined `course_sessions` — two `id` columns, which
     * MySQL rejects outright as "Duplicate column name 'id'" and SQLite allows
     * without comment. This page rendered on my SQLite copy and 500'd on the
     * live MySQL for exactly that reason.
     *
     * The published scope is spelled out here rather than taken from
     * CourseModel::live(), because it is the model's `courses.*` select that
     * caused the problem. Kept in step with live() deliberately: published, and
     * either no publish date or one that has passed.
     */
    private function libraryCount(): int
    {
        $row = db_connect()->table('courses c')
            ->select('COUNT(DISTINCT c.id) AS n', false)
            ->join('course_sessions cs', "cs.course_id = c.id AND cs.mode = 'SELF_PACED' AND cs.status = 'open'", 'inner', false)
            ->where('c.status', 'published')
            ->where('c.deleted_at', null)
            ->groupStart()
                ->where('c.published_at IS NULL')
                ->orWhere('c.published_at <=', date('Y-m-d H:i:s'))
            ->groupEnd()
            ->get()->getRowArray();

        return (int) ($row['n'] ?? 0);
    }

    /**
     * Their total runtime, in whole hours, or 0 when nothing is recorded.
     *
     * EXISTS rather than a join to `course_sessions`. Joining fans the rows
     * out: a course offered as two self-paced sessions would have every one of
     * its lessons counted twice, and the page would advertise a library twice
     * the size of the one a member gets. EXISTS asks the question the sentence
     * actually asks — is this course in the library — without multiplying the
     * rows being summed.
     */
    private function libraryHours(): int
    {
        $row = db_connect()->table('lessons l')
            ->select('SUM(l.duration_sec) AS total', false)
            ->join('courses c', 'c.id = l.course_id', 'inner')
            ->where('l.status', 'published')
            ->where('c.status', 'published')
            ->where('c.deleted_at', null)
            ->where("EXISTS (SELECT 1 FROM course_sessions cs
                             WHERE cs.course_id = c.id
                               AND cs.mode = 'SELF_PACED'
                               AND cs.status = 'open')", null, false)
            ->get()->getRowArray();

        return (int) floor(((int) ($row['total'] ?? 0)) / 3600);
    }
}
