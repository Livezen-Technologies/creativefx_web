<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// The bare root is the welcome page — the trilingual entry choice Clause 3.9 A
// asks for — and it is declared by the Site module along with every other
// localized web route (modules/Site/Config/Routes.php). It used to redirect
// straight to the default locale from here, which ran first and made the
// welcome page unreachable: an English speaker's default is not a choice.
// API/admin routes are defined by the Auth/Admin modules.
