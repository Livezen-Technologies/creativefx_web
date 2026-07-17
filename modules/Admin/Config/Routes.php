<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('admin', ['namespace' => 'Modules\Admin\Controllers'], static function (RouteCollection $routes): void {
    // Public auth pages (session-based).
    $routes->get('login', 'Auth::loginForm');
    $routes->post('login', 'Auth::login');
    $routes->get('logout', 'Auth::logout');

    // Everything else requires an admin session.
    $routes->group('', ['filter' => 'adminauth'], static function (RouteCollection $routes): void {
        $routes->get('/', 'Dashboard::index');

        // Generic CRUD resources: segment => controller.
        $resources = [
            'pages'       => 'Pages',
            'categories'  => 'Categories',
            'esg-metrics' => 'EsgMetrics',
            'settings'    => 'Settings',
            'jobs'        => 'Jobs',
            'contacts'    => 'Contacts',
            'leads'       => 'Leads',
            'showroom-categories' => 'ShowroomCategories',
            'showroom-products'   => 'ShowroomProducts',
            'news-categories'     => 'NewsCategories',
            'news-posts'          => 'NewsPosts',
        ];
        foreach ($resources as $seg => $ctrl) {
            $routes->get($seg, $ctrl . '::index');
            $routes->get($seg . '/new', $ctrl . '::create');
            $routes->post($seg, $ctrl . '::store');
            $routes->get($seg . '/(:num)/edit', $ctrl . '::edit/$1');
            $routes->post($seg . '/(:num)', $ctrl . '::update/$1');
            $routes->post($seg . '/(:num)/delete', $ctrl . '::delete/$1');
        }

        // HR recruitment dashboard (custom pipeline UI, not generic CRUD).
        $routes->get('applications', 'Applications::index');
        $routes->get('applications/export', 'Applications::export');
        $routes->get('applications/(:num)', 'Applications::show/$1');
        $routes->post('applications/(:num)', 'Applications::update/$1');
        $routes->get('applications/(:num)/cv', 'Applications::download/$1');

        // Page builder (block-content editor + structure operations).
        $routes->get('pages/(:num)/content', 'Content::edit/$1');
        $routes->post('pages/(:num)/content', 'Content::update/$1');
        $routes->post('pages/(:num)/sections', 'Content::addSection/$1');
        $routes->post('sections/(:num)/move', 'Content::moveSection/$1');
        $routes->post('sections/(:num)/toggle', 'Content::toggleSection/$1');
        $routes->post('sections/(:num)/delete', 'Content::deleteSection/$1');
        $routes->post('sections/(:num)/blocks', 'Content::addBlock/$1');
        $routes->post('blocks/(:num)/move', 'Content::moveBlock/$1');
        $routes->post('blocks/(:num)/toggle', 'Content::toggleBlock/$1');
        $routes->post('blocks/(:num)/delete', 'Content::deleteBlock/$1');

        // Translation Manager.
        $routes->get('translations', 'Translations::index');
        $routes->post('translations', 'Translations::save');
        $routes->post('translations/add', 'Translations::addKey');
        $routes->post('translations/import', 'Translations::import');

        // Launch video manager.
        $routes->get('videos', 'Videos::edit');
        $routes->post('videos', 'Videos::update');

        // Media library.
        $routes->get('media', 'Media::index');
        $routes->post('media/upload', 'Media::upload');
        $routes->post('media/(:num)/delete', 'Media::delete/$1');
    });
});
