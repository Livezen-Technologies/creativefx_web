<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Bare root redirects to the default locale. All localized web routes are
// defined by the Site module (modules/Site/Config/Routes.php); API/admin
// routes are defined by the Auth/Admin modules.
$routes->get('/', static fn () => redirect()->to('/' . config('App')->defaultLocale));
