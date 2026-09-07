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
            'pages'               => 'Pages',
            'menu-items'          => 'MenuItems',
            'news-categories'     => 'NewsCategories',
            'news-posts'          => 'NewsPosts',
            'jobs'                => 'Jobs',
            'settings'            => 'Settings',
            'contacts'            => 'Contacts',
            'leads'               => 'Leads',

            // The catalogue.
            'course-categories'   => 'CourseCategories',
            'courses'             => 'Courses',
            'instructors'         => 'Instructors',
            'venues'              => 'Venues',
            'bundles'             => 'Bundles',
            'resources'           => 'Resources',
            'webinars'            => 'Webinars',

            // Commerce.
            'coupons'             => 'Coupons',
        ];
        foreach ($resources as $seg => $ctrl) {
            $routes->get($seg, $ctrl . '::index');
            $routes->get($seg . '/new', $ctrl . '::create');
            $routes->post($seg, $ctrl . '::store');
            $routes->get($seg . '/(:num)/edit', $ctrl . '::edit/$1');
            $routes->post($seg . '/(:num)', $ctrl . '::update/$1');
            $routes->post($seg . '/(:num)/delete', $ctrl . '::delete/$1');
        }

        // The recruitment pipeline: applications received against the
        // vacancies in Clause 3.9 G.
        $routes->get('applications', 'Applications::index');
        $routes->get('applications/export', 'Applications::export');
        $routes->get('applications/(:num)', 'Applications::show/$1');
        $routes->post('applications/(:num)', 'Applications::update/$1');
        $routes->get('applications/(:num)/cv', 'Applications::download/$1');

        // Queues rather than CRUD: each of these is a decision somebody makes
        // about something that arrived, not a record they author, so they get
        // their own screens. Sessions are here rather than in $resources
        // because seat counts are inventory and must never be a text input.
        $routes->get('course-sessions', 'CourseSessions::index');
        $routes->get('course-sessions/new', 'CourseSessions::create');
        $routes->post('course-sessions', 'CourseSessions::store');
        $routes->get('course-sessions/(:num)', 'CourseSessions::show/$1');
        $routes->post('course-sessions/(:num)', 'CourseSessions::update/$1');
        $routes->post('course-sessions/(:num)/cancel', 'CourseSessions::cancel/$1');
        $routes->post('course-sessions/(:num)/attendance', 'CourseSessions::attendance/$1');

        $routes->get('orders', 'Orders::index');
        $routes->get('orders/(:num)', 'Orders::show/$1');
        $routes->post('orders/(:num)/mark-paid', 'Orders::markPaid/$1');
        $routes->post('orders/(:num)/refund', 'Orders::refund/$1');

        $routes->get('enrolments', 'Enrolments::index');
        $routes->post('enrolments/(:num)', 'Enrolments::update/$1');
        $routes->post('enrolments/(:num)/certificate', 'Enrolments::issueCertificate/$1');
        // Withdrawing and downloading a certificate. CertificateService::revoke()
        // had existed since the module was written with nothing routed to it, so
        // a certificate issued in error could never be taken back.
        $routes->post('enrolments/(:num)/certificate/revoke', 'Enrolments::revokeCertificate/$1');
        $routes->get('enrolments/(:num)/certificate/download', 'Enrolments::downloadCertificate/$1');

        $routes->get('training-leads', 'TrainingLeads::index');
        $routes->get('training-leads/(:num)', 'TrainingLeads::show/$1');
        $routes->post('training-leads/(:num)', 'TrainingLeads::update/$1');

        $routes->get('reviews', 'Reviews::index');
        $routes->post('reviews/(:num)', 'Reviews::update/$1');
        $routes->post('reviews/(:num)/delete', 'Reviews::delete/$1');

        // Page builder (block-content editor + structure operations).
        $routes->get('pages/(:num)/content', 'Content::edit/$1');
        $routes->post('pages/(:num)/content', 'Content::update/$1');
        $routes->post('dashboard/layout/(:segment)/toggle', 'Dashboard::toggleWidget/$1');
        $routes->post('dashboard/layout/(:segment)/size', 'Dashboard::resizeWidget/$1');
        $routes->post('dashboard/layout/(:segment)/move', 'Dashboard::moveWidget/$1');
        $routes->post('dashboard/layout/reset', 'Dashboard::resetLayout');

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
