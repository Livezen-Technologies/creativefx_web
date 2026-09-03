<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Tshda\Libraries\SiteSearch;

/**
 * Site-wide search (Clause 3.12): across page content, news, documents,
 * directory entries, services and gallery metadata, in all three languages,
 * sortable and paginated.
 */
class Search extends BaseController
{
    private const PER_PAGE = 20;

    public function index(?string $locale = null)
    {
        helper('norlanka');

        $query = trim((string) $this->request->getGet('q'));
        $type  = trim((string) $this->request->getGet('type'));
        $sort  = trim((string) $this->request->getGet('sort')) ?: 'relevance';
        $page  = max(1, (int) $this->request->getGet('page'));

        $results = $counts = [];
        $total   = 0;

        if ($query !== '') {
            $search  = new SiteSearch();
            $all     = $search->run($query, current_locale());
            $counts  = SiteSearch::countByType($all);

            if ($type !== '') {
                $all = array_values(array_filter($all, static fn (array $r): bool => $r['type'] === $type));
            }

            $all     = SiteSearch::sort($all, $sort);
            $total   = count($all);
            $results = array_slice($all, ($page - 1) * self::PER_PAGE, self::PER_PAGE);
        }

        return view('Modules\Tshda\Views\search', [
            'query'           => $query,
            'type'            => $type,
            'sort'            => $sort,
            'results'         => $results,
            'counts'          => $counts,
            'total'           => $total,
            'page'            => $page,
            'pageCount'       => (int) ceil($total / self::PER_PAGE),
            'perPage'         => self::PER_PAGE,
            'title'           => ($query !== '' ? $query . ' — ' : '') . lang('Site.search.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.search.meta'),
            // A search results page is not content; indexing it produces
            // thousands of near-duplicate URLs and buries the pages that are.
            'noIndex'         => true,
        ]);
    }
}
