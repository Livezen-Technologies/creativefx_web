<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// REST API (JSON). Login is public; everything else requires a valid JWT.
$routes->group('api', ['namespace' => 'Modules\Auth\Controllers\Api'], static function (RouteCollection $routes): void {
    $routes->post('auth/login', 'AuthController::login');

    $routes->group('', ['filter' => 'jwt'], static function (RouteCollection $routes): void {
        $routes->get('auth/me', 'AuthController::me');
    });
});
