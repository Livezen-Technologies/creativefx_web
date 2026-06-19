<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Public CRM endpoints (JSON). The contact form posts here. Leading backslash
// makes the handler fully-qualified (otherwise CI4 prepends App\Controllers).
$routes->post('api/contact', '\Modules\Crm\Controllers\Api\ContactController::submit');
