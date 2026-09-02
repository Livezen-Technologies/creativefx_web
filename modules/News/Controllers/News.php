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
            'title'           => lang('Site.news.title') . ' — ' . setting('site_name', 'Magic Corn'),
            'metaDescription' => lang('Site.news.meta'),
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
            'title'           => t_field($post['meta_title'] ?: $post['title']) . ' — ' . setting('site_name', 'Magic Corn'),
            'metaDescription' => t_field($post['meta_description'] ?: $post['excerpt']),
            'ogImage'         => $post['image'] ?? null,
        ]);
    }
}
