<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Admin panel (skeleton). The login page is public; the dashboard is gated by
// the JWT filter — JwtAuthFilter redirects unauthenticated /admin requests here.
$routes->group('admin', ['namespace' => 'Modules\Admin\Controllers'], static function (RouteCollection $routes): void {
    $routes->get('login', 'Auth::login');
    $routes->get('/', 'Dashboard::index', ['filter' => 'jwt']);
});
