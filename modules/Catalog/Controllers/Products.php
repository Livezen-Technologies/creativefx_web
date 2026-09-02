<?php

namespace Modules\Catalog\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Catalog\Models\ProductCategoryModel;
use Modules\Catalog\Models\ProductModel;

/**
 * Public product catalog: filterable listing (category + merchandising label)
 * and the product detail page with spec sheet, gallery and — when a GLB/GLTF
 * model is attached — an interactive 3D viewer.
 */
class Products extends BaseController
{
    private const PER_PAGE = 12;

    public function index(?string $locale = null)
    {
        helper('norlanka');

        $categories = (new ProductCategoryModel())->published();

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

        $label = trim((string) $this->request->getGet('label'));
        if ($label !== '' && $label !== 'featured' && ! in_array($label, ProductModel::LABELS, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $model    = new ProductModel();
        $products = $model->published($activeCategory['id'] ?? null, $label ?: null)->paginate(self::PER_PAGE);
        $pager    = $model->pager;

        return view('Modules\Catalog\Views\index', [
            'products'        => $products,
            'categories'      => $categories,
            'activeCategory'  => $activeCategory,
            'activeLabel'     => $label,
            'currentPage'     => $pager->getCurrentPage(),
            'pageCount'       => $pager->getPageCount(),
            'title'           => lang('Site.products.title') . ' — ' . setting('site_name', 'Magic Corn'),
            'metaDescription' => lang('Site.products.meta'),
        ]);
    }

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $model   = new ProductModel();
        $product = $model->findPublishedBySlug((string) $slug);
        if ($product === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $category = ! empty($product['category_id'])
            ? (new ProductCategoryModel())->find((int) $product['category_id'])
            : null;

        return view('Modules\Catalog\Views\show', [
            'product'         => $product,
            'category'        => $category,
            'related'         => $model->related($product),
            'title'           => t_field($product['meta_title'] ?: $product['name']) . ' — ' . setting('site_name', 'Magic Corn'),
            'metaDescription' => t_field($product['meta_description'] ?: $product['short_description']),
            'ogImage'         => $product['hero_image'] ?? null,
        ]);
    }
}
