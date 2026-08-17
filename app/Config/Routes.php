<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Bare root redirects to the default locale. All localized web routes are
// defined by the Site module (modules/Site/Config/Routes.php); API/admin
// routes are defined by the Auth/Admin modules.
$routes->get('/', static fn () => redirect()->to('/' . config('App')->defaultLocale));

// Locale-independent, so it sits here rather than in the Site module's
// locale-prefixed route file.
$routes->get('sitemap.xml', '\Modules\Site\Controllers\Sitemap::index');
