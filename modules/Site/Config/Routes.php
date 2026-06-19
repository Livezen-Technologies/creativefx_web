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

// CMS catch-all: /{locale}/{slug} -> PageController::show($slug)  ($2 = slug)
$routes->get('(:locale)/(:segment)', 'PageController::show/$2', $siteOptions);
