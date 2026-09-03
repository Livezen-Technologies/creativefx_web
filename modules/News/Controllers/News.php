<?php

namespace Modules\News\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\News\Models\NewsCategoryModel;
use Modules\News\Models\NewsPostModel;

/**
 * Public newsroom: paginated article listing with category filter, plus the
 * article detail page with related posts. Only published posts whose
 * published_at has arrived are visible (scheduled publishing).
 */
class News extends BaseController
{
    private const PER_PAGE = 9;

    public function index(?string $locale = null)
    {
        helper('norlanka');

        $categories = (new NewsCategoryModel())->published();

        // Optional ?category=slug filter — unknown slugs 404.
        $activeCategory = null;
        $catSlug        = trim((string) $this->request->getGet('category'));
        if ($catSlug !== '') {
            foreach ($categories as $c) {
                if ($c['slug'] === $catSlug) {
                    $activeCategory = $c;
                    break;
                }
            }
            if ($activeCategory === null) {
                throw PageNotFoundException::forPageNotFound();
            }
        }

        $postModel = new NewsPostModel();
        $posts     = $postModel->live($activeCategory['id'] ?? null)->paginate(self::PER_PAGE);
        $pager     = $postModel->pager;

        return view('Modules\News\Views\index', [
            'posts'           => $posts,
            'categories'      => $categories,
            'activeCategory'  => $activeCategory,
            'currentPage'     => $pager->getCurrentPage(),
            'pageCount'       => $pager->getPageCount(),
            'title'           => lang('Site.news.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.news.meta'),
        ]);
    }

    /**
     * Announcements and notices (Clause 3.9 E.c and B.II).
     *
     * The same listing as the newsroom, pinned to the announcements category
     * and shown with the alert-subscription form, because the two belong
     * together: the page that tells you a fertilizer issue has been notified is
     * the page where you ask to be told about the next one.
     *
     * A separate method rather than /news?category=announcements so the section
     * has its own address, its own place in the menu, and its own title — a
     * query string is a filter, not a section of a government website.
     */
    public function announcements(?string $locale = null)
    {
        helper('norlanka');

        $categories = (new NewsCategoryModel())->published();

        $category = null;
        foreach ($categories as $c) {
            if ($c['slug'] === 'announcements') {
                $category = $c;
                break;
            }
        }

        $postModel = new NewsPostModel();
        // No announcements category yet means show everything rather than
        // nothing: an empty page here reads as an Authority with no notices.
        $posts = $postModel->live($category['id'] ?? null)->paginate(self::PER_PAGE);
        $pager = $postModel->pager;

        return view('Modules\News\Views\index', [
            'posts'           => $posts,
            'categories'      => $categories,
            'activeCategory'  => $category,
            'isAnnouncements' => true,
            'currentPage'     => $pager->getCurrentPage(),
            'pageCount'       => $pager->getPageCount(),
            'title'           => lang('Site.announcements.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.announcements.meta'),
        ]);
    }

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $postModel = new NewsPostModel();
        $post      = $postModel->findLiveBySlug((string) $slug);
        if ($post === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $category = ! empty($post['category_id'])
            ? (new NewsCategoryModel())->find((int) $post['category_id'])
            : null;

        return view('Modules\News\Views\post', [
            'post'            => $post,
            'category'        => $category,
            'related'         => $postModel->related($post),
            'title'           => t_field($post['meta_title'] ?: $post['title']) . ' — ' . setting('site_name', ''),
            'metaDescription' => t_field($post['meta_description'] ?: $post['excerpt']),
            'ogImage'         => $post['image'] ?? null,
        ]);
    }
}
