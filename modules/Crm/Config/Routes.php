<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Public CRM endpoints (JSON). Leading backslash makes the handler
// fully-qualified (otherwise CI4 prepends App\Controllers).
$routes->post('api/contact', '\Modules\Crm\Controllers\Api\ContactController::submit');
$routes->post('api/inquiry', '\Modules\Crm\Controllers\Api\InquiryController::submit');
$routes->post('api/booking', '\Modules\Crm\Controllers\Api\BookingController::submit');
