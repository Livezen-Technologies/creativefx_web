<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use Modules\Cms\Libraries\PageSeo;
use Modules\Cms\Models\PageModel;
use Modules\News\Models\NewsPostModel;
use Modules\Tshda\Models\DiscussionCommentModel;
use Modules\Tshda\Models\DiscussionTopicModel;
use Modules\Tshda\Models\NoticeModel;
use Modules\Tshda\Models\OrgLinkModel;
use Modules\Tshda\Models\ProgrammeModel;
use Modules\Tshda\Models\ServiceModel;
use Modules\Tshda\Models\StatisticModel;

/**
 * The home page (Clause 3.9 B): seven modules, each independently manageable,
 * each reading from its own table.
 *
 *   B.I   stakeholder service clusters
 *   B.II  press releases, announcements, news and events
 *   B.III the Authority's Facebook wall, loaded asynchronously
 *   B.IV  priority notices
 *   B.V   the current discussion topic and its moderated comments
 *   B.VI  media gallery
 *   B.VII links to related organisations
 *
 * Every query is wrapped, because a home page is the one page that must render
 * when something else is broken. A missing table or a failed query costs a
 * module, not the page.
 */
class Home extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        $pageModel = new PageModel();
        $page      = $pageModel->findHome();

        $seo = PageSeo::for($page)->toViewData(current_url());

        return view('Modules\Site\Views\home\index', $seo + [
            'page'       => $page,
            'notices'    => $this->safe(static fn () => (new NoticeModel())->current(4), []),
            'clusters'   => $this->safe(static fn () => (new ServiceModel())->clusters(), []),
            'posts'      => $this->safe(static fn () => (new NewsPostModel())->latest(4), []),
            'programmes' => $this->safe(static fn () => (new ProgrammeModel())->calendar(3), []),
            'datasets'   => $this->safe(static fn () => (new StatisticModel())->live(), []),
            'topic'      => $this->safe(static fn () => (new DiscussionTopicModel())->current(), null),
            'comments'   => [],
            'links'      => $this->safe(static fn () => (new OrgLinkModel())->live('related'), []),
        ] + $this->discussion());
    }

    /** The current topic's approved comments, if there is a current topic. */
    private function discussion(): array
    {
        $topic = $this->safe(static fn () => (new DiscussionTopicModel())->current(), null);
        if ($topic === null) {
            return [];
        }

        return [
            'comments' => $this->safe(
                static fn () => (new DiscussionCommentModel())->approved((int) $topic['id'], 3),
                []
            ),
        ];
    }

    /** @param callable $fn */
    private function safe(callable $fn, $fallback)
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            log_message('error', 'Home module query failed: ' . $e->getMessage());

            return $fallback;
        }
    }
}
