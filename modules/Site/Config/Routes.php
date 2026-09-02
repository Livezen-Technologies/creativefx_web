<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Constrain the locale segment to the actual supported locales so the localized
// routes never shadow reserved prefixes like /admin or /api, regardless of the
// order in which module route files are discovered.
$routes->addPlaceholder('locale', implode('|', config('App')->supportedLocales));

// Languages the site used to be published in. Dropping them from
// supportedLocales makes every /ja, /es and /zh URL a 404 — including anything
// already linked or indexed — so they are redirected to the same path under the
// default locale instead of disappearing. Permanent, because the move is.
$retiredLocales = ['ja', 'es', 'zh'];
$routes->addPlaceholder('retiredlocale', implode('|', $retiredLocales));
$routes->get('(:retiredlocale)', static fn () => redirect()->to('/' . config('App')->defaultLocale, 301));
// Both captures are passed, in order — the locale first, then the rest of the
// path. Naming only the second would silently redirect /ja/about-us to /en/ja.
$routes->get('(:retiredlocale)/(:any)', static fn (string $locale, string $rest = '') => redirect()->to(
    '/' . config('App')->defaultLocale . '/' . $rest,
    301,
));

$siteOptions = ['filter' => 'applocale', 'namespace' => 'Modules\Site\Controllers'];

// Localized home, e.g. /en, /ja. The leading capture is the locale (the
// applocale filter reads + validates it from the URI).
$routes->get('(:locale)', 'Home::index', $siteOptions);

// Virtual Showroom (3D) — defined before the CMS catch-all so it wins.
// $routes->get('(:locale)/showroom', '\Modules\Showroom\Controllers\Showroom::index/$1', ['filter' => 'applocale']);   // retired: no Magic Corn equivalent
// $routes->get('(:locale)/showroom/(:segment)', '\Modules\Showroom\Controllers\Showroom::scene/$1/$2', ['filter' => 'applocale']);   // retired: no Magic Corn equivalent

// Careers portal (job detail + application). Co-located here (not in the
// Careers module's own routes file) so the (:locale) placeholder above is
// guaranteed to exist regardless of module discovery order.
// $routes->get('(:locale)/careers/(:segment)', '\Modules\Careers\Controllers\Careers::show/$1/$2', ['filter' => 'applocale']);   // retired: no Magic Corn equivalent
// $routes->post('(:locale)/careers/(:segment)/apply', '\Modules\Careers\Controllers\Careers::apply/$1/$2', ['filter' => 'applocale']);   // retired with the careers pages

// Newsroom (listing + article detail). Co-located here for the same
// (:locale) placeholder-ordering reason as the careers routes above.
$routes->get('(:locale)/news', '\Modules\News\Controllers\News::index/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/news/(:segment)', '\Modules\News\Controllers\News::show/$1/$2', ['filter' => 'applocale']);

// Product catalog (listing + product detail with GLB 3D viewer).
$routes->get('(:locale)/products', '\Modules\Catalog\Controllers\Products::index/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/products/(:segment)', '\Modules\Catalog\Controllers\Products::show/$1/$2', ['filter' => 'applocale']);

// CMS catch-all: /{locale}/{slug} -> PageController::show($slug)  ($2 = slug)
$routes->get('(:locale)/(:segment)', 'PageController::show/$2', $siteOptions);
