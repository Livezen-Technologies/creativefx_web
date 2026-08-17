<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Constrain the locale segment to the actual supported locales so the localized
// routes never shadow reserved prefixes like /admin or /api, regardless of the
// order in which module route files are discovered.
$routes->addPlaceholder('locale', implode('|', config('App')->supportedLocales));

$siteOptions = ['filter' => 'applocale', 'namespace' => 'Modules\Site\Controllers'];

// Localized home, e.g. /en, /si. The leading capture is the locale (the
// applocale filter reads + validates it from the URI).
$routes->get('(:locale)', 'Home::index', $siteOptions);

// --- Services -------------------------------------------------------------
// The overview is an ordinary single-segment CMS page; each service page is
// stored under the slug "services/{slug}", which the CMS catch-all below can
// never match because it only takes one segment.
$routes->get('(:locale)/services/(:segment)', 'PageController::service/$2', $siteOptions);

// --- Portfolio ------------------------------------------------------------
$routes->get('(:locale)/portfolio', '\Modules\Portfolio\Controllers\Portfolio::index/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/portfolio/(:segment)', '\Modules\Portfolio\Controllers\Portfolio::show/$1/$2', ['filter' => 'applocale']);

// --- Quote request (the primary conversion path) --------------------------
$routes->get('(:locale)/quote', '\Modules\Crm\Controllers\Quote::index/$1', ['filter' => 'applocale']);
$routes->post('(:locale)/quote', '\Modules\Crm\Controllers\Quote::submit/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/quote/thanks', '\Modules\Crm\Controllers\Quote::thanks/$1', ['filter' => 'applocale']);
// Footer newsletter signup — its own endpoint so an email-only signup is never
// put through the quote form's validation.
$routes->post('(:locale)/subscribe', '\Modules\Crm\Controllers\Quote::subscribe/$1', ['filter' => 'applocale']);

// CMS catch-all: /{locale}/{slug} -> PageController::show($slug)  ($2 = slug)
// Declared LAST so every reserved prefix above wins.
$routes->get('(:locale)/(:segment)', 'PageController::show/$2', $siteOptions);

/*
 * Retired with the CreativeFX rebuild (2026-08-17): the Norlanka apparel
 * sections — the 3D showroom, careers portal, newsroom and product catalog.
 * The modules are still on disk and their data is untouched; only the public
 * routes are gone, so nothing from the old site is reachable. Restore a
 * section by putting its route back above the catch-all.
 *
 *   $routes->get('(:locale)/showroom', '\Modules\Showroom\Controllers\Showroom::index/$1', ['filter' => 'applocale']);
 *   $routes->get('(:locale)/showroom/(:segment)', '\Modules\Showroom\Controllers\Showroom::scene/$1/$2', ['filter' => 'applocale']);
 *   $routes->get('(:locale)/careers/(:segment)', '\Modules\Careers\Controllers\Careers::show/$1/$2', ['filter' => 'applocale']);
 *   $routes->post('(:locale)/careers/(:segment)/apply', '\Modules\Careers\Controllers\Careers::apply/$1/$2', ['filter' => 'applocale']);
 *   $routes->get('(:locale)/news', '\Modules\News\Controllers\News::index/$1', ['filter' => 'applocale']);
 *   $routes->get('(:locale)/news/(:segment)', '\Modules\News\Controllers\News::show/$1/$2', ['filter' => 'applocale']);
 *   $routes->get('(:locale)/products', '\Modules\Catalog\Controllers\Products::index/$1', ['filter' => 'applocale']);
 *   $routes->get('(:locale)/products/(:segment)', '\Modules\Catalog\Controllers\Products::show/$1/$2', ['filter' => 'applocale']);
 */
