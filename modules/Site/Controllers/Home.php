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
            'heroSlides' => $this->safe(fn () => $this->heroSlides(), []),
        ] + $this->discussion());
    }

    /**
     * The photographs behind the hero panel.
     *
     * They live in the Media library's `hero` folder rather than in a table of
     * their own, so the CMT adds one exactly the way they add any other image,
     * and the caption is the media record's own `alt` — a locale map, so a
     * photograph can be described in all three languages instead of carrying an
     * English description into a Tamil page.
     *
     * Ordered by the uploaded filename, so prefixing 01-, 02- sets the
     * sequence. Upload order would leave no way to reorder a slideshow short of
     * deleting and re-uploading it.
     *
     * A row whose file has been removed from disk is skipped rather than
     * rendered as a broken image: the hero is the first thing anyone sees.
     */
    private function heroSlides(int $limit = 6): array
    {
        $rows = model('Modules\Media\Models\MediaModel')
            ->where('folder', 'hero')
            ->like('mime_type', 'image/', 'after')
            ->orderBy('original_name', 'ASC')
            ->findAll($limit);

        $slides = [];

        foreach ($rows as $row) {
            $path = ltrim((string) ($row['path'] ?? ''), '/');
            if ($path === '' || ! is_file(FCPATH . $path)) {
                continue;
            }

            // The uploader writes a WebP sibling beside every image it accepts.
            // Offer it first and keep the original as the fallback source, so a
            // browser that cannot decode WebP still gets the photograph.
            $webp = preg_replace('/\.[a-z0-9]+$/i', '.webp', $path);

            $slides[] = [
                'src'    => (string) ($row['url'] ?? '/' . $path),
                'webp'   => $webp !== $path && is_file(FCPATH . $webp) ? '/' . $webp : null,
                'alt'    => t_field($row['alt'] ?? null),
                'width'  => isset($row['width']) ? (int) $row['width'] : null,
                'height' => isset($row['height']) ? (int) $row['height'] : null,
            ];
        }

        return $slides;
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
