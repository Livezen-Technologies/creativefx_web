<?php

namespace Modules\Commerce\Controllers;

use App\Controllers\BaseController;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Catalog\Models\CourseModel;
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

    /** How many courses a pass actually opens. */
    private function libraryCount(): int
    {
        return (new CourseModel())->live()
            ->join('course_sessions cs', 'cs.course_id = courses.id', 'inner')
            ->where('cs.mode', 'SELF_PACED')
            ->where('cs.status', 'open')
            ->groupBy('courses.id')
            ->countAllResults();
    }

    /** Their total runtime, in whole hours, or 0 when nothing is recorded. */
    private function libraryHours(): int
    {
        $row = db_connect()->table('lessons l')
            ->select('SUM(l.duration_sec) AS total', false)
            ->join('courses c', 'c.id = l.course_id', 'inner')
            ->join('course_sessions cs', "cs.course_id = c.id AND cs.mode = 'SELF_PACED'", 'inner', false)
            ->where('l.status', 'published')
            ->get()->getRowArray();

        return (int) floor(((int) ($row['total'] ?? 0)) / 3600);
    }
}
