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
            'menu-items'  => 'MenuItems',
            'rooms'       => 'Rooms',
            'locations'   => 'Locations',
            'esg-metrics' => 'EsgMetrics',
            'settings'    => 'Settings',
            'contacts'    => 'Contacts',
            'leads'       => 'Leads',
            'news-categories'     => 'NewsCategories',
            'news-posts'          => 'NewsPosts',
            // Retired with the apparel and manufacturing build this codebase
            // started as: a product catalogue, a 3D showroom and a careers
            // portal, none of which this hotel routes on the public site. The
            // controllers, models and tables are untouched — only the way in
            // is gone — so restoring one is a line here and a line in the
            // sidebar rather than a rebuild.
            //   'categories' => 'Categories',
            //   'products'   => 'Products',
            //   'showroom-categories' => 'ShowroomCategories',
            //   'showroom-products'   => 'ShowroomProducts',
            //   'jobs'       => 'Jobs',
        ];
        foreach ($resources as $seg => $ctrl) {
            $routes->get($seg, $ctrl . '::index');
            $routes->get($seg . '/new', $ctrl . '::create');
            $routes->post($seg, $ctrl . '::store');
            $routes->get($seg . '/(:num)/edit', $ctrl . '::edit/$1');
            $routes->post($seg . '/(:num)', $ctrl . '::update/$1');
            $routes->post($seg . '/(:num)/delete', $ctrl . '::delete/$1');
        }

        // The HR recruitment dashboard is retired with the careers portal it
        // belonged to — the public /careers routes were commented out when this
        // became a hotel, so the pipeline had no applications to receive.
        //   $routes->get('applications', 'Applications::index');
        //   $routes->get('applications/export', 'Applications::export');
        //   $routes->get('applications/(:num)', 'Applications::show/$1');
        //   $routes->post('applications/(:num)', 'Applications::update/$1');
        //   $routes->get('applications/(:num)/cv', 'Applications::download/$1');

        // Page builder (block-content editor + structure operations).
        $routes->get('pages/(:num)/content', 'Content::edit/$1');
        $routes->post('pages/(:num)/content', 'Content::update/$1');
        $routes->get('tasks', 'Tasks::index');
        $routes->post('tasks/(:segment)/run', 'Tasks::run/$1');
        $routes->post('tasks/(:segment)/toggle', 'Tasks::toggle/$1');

        $routes->get('analytics', 'Analytics::index');
        $routes->get('analytics/export', 'Analytics::export');

        // The guided settings screen. The raw key/value CRUD stays at
        // admin/settings for anything this form does not declare.
        $routes->get('site-settings', 'SiteSettings::index');
        $routes->get('site-settings/(:segment)', 'SiteSettings::index/$1');
        $routes->post('site-settings/(:segment)', 'SiteSettings::save/$1');
        $routes->post('site-settings-test-email', 'SiteSettings::testEmail');
        $routes->post('site-settings-test-recaptcha', 'SiteSettings::testRecaptcha');

        $routes->post('pages/(:num)/content/reset', 'Content::resetToDefault/$1');
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
        $routes->get('media/list', 'Media::list');
        $routes->post('media/upload', 'Media::upload');
        $routes->post('media/upload-ajax', 'Media::uploadAjax');
        $routes->post('media/(:num)/rename', 'Media::rename/$1');
        $routes->post('media/(:num)/move', 'Media::move/$1');
        $routes->post('media/(:num)/replace', 'Media::replace/$1');
        $routes->post('media/(:num)/meta', 'Media::updateMeta/$1');
        $routes->post('media/(:num)/delete', 'Media::delete/$1');
    });
});
