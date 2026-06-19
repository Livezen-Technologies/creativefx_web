<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Constrain the locale segment to the actual supported locales so the localized
// routes never shadow reserved prefixes like /admin or /api, regardless of the
// order in which module route files are discovered.
$routes->addPlaceholder('locale', implode('|', config('App')->supportedLocales));

$siteOptions = ['filter' => 'applocale', 'namespace' => 'Modules\Site\Controllers'];

// Localized home, e.g. /en, /ja. The leading capture is the locale (the
// applocale filter reads + validates it from the URI).
$routes->get('(:locale)', 'Home::index', $siteOptions);

// Virtual Showroom (3D) — defined before the CMS catch-all so it wins.
$routes->get('(:locale)/showroom', '\Modules\Showroom\Controllers\Showroom::index/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/showroom/(:segment)', '\Modules\Showroom\Controllers\Showroom::scene/$1/$2', ['filter' => 'applocale']);

// CMS catch-all: /{locale}/{slug} -> PageController::show($slug)  ($2 = slug)
$routes->get('(:locale)/(:segment)', 'PageController::show/$2', $siteOptions);
