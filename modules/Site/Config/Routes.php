<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Constrain the locale segment to the actual supported locales so the localized
// routes never shadow reserved prefixes like /admin or /api, regardless of the
// order in which module route files are discovered.
$routes->addPlaceholder('locale', implode('|', config('App')->supportedLocales));

$siteOptions  = ['filter' => 'applocale', 'namespace' => 'Modules\Site\Controllers'];
$tshdaOptions = ['filter' => 'applocale', 'namespace' => 'Modules\Tshda\Controllers'];

// The welcome page (Clause 3.9 A): the trilingual entry choice, at the root,
// outside the locale group because it is what a visitor reaches before they
// have chosen a language.
$routes->get('/', 'Welcome::index', ['namespace' => 'Modules\Tshda\Controllers']);

// The sitemap sits outside the locale group on purpose: it lists every language
// and is fetched by crawlers at a fixed, unprefixed address. Declared before the
// locale routes so nothing else can claim it.
$routes->get('sitemap.xml', 'Sitemap::index', ['namespace' => 'Modules\Site\Controllers']);

// Outbound clicks the server cannot otherwise see. Outside the locale group
// because the page reporting one already knows its own path.
$routes->post('api/track', 'Track::event', ['namespace' => 'Modules\Analytics\Controllers']);

// Localized home, e.g. /en, /si, /ta. The leading capture is the locale (the
// applocale filter reads + validates it from the URI).
$routes->get('(:locale)', 'Home::index', $siteOptions);

// ── The Authority's own sections ────────────────────────────────────────────
// All declared before the CMS catch-all, which would otherwise swallow every
// one of them as a page slug.

// Services (Clause 3.9 D and E).
$routes->get('(:locale)/services', 'Services::index/$1', $tshdaOptions);
$routes->get('(:locale)/services/(:segment)', 'Services::show/$1/$2', $tshdaOptions);

// Staff and contact directory (E.d, J.b).
$routes->get('(:locale)/directory', 'Directory::index/$1', $tshdaOptions);

// Statistics (F). The CSV route is declared before the detail route so that
// /statistics/foo.csv is an export and not a dataset whose slug ends in ".csv".
$routes->get('(:locale)/statistics', 'Statistics::index/$1', $tshdaOptions);
$routes->get('(:locale)/statistics/(:segment)/csv', 'Statistics::csv/$1/$2', $tshdaOptions);
$routes->get('(:locale)/statistics/(:segment)', 'Statistics::show/$1/$2', $tshdaOptions);

// Downloads (H).
$routes->get('(:locale)/downloads', 'Downloads::index/$1', $tshdaOptions);
$routes->get('(:locale)/downloads/(:segment)', 'Downloads::file/$1/$2', $tshdaOptions);

// Hantana National Training Centre booking (Clause 3.1.IV).
$routes->get('(:locale)/hantana', 'Hantana::index/$1', $tshdaOptions);
$routes->get('(:locale)/hantana/(:segment)', 'Hantana::show/$1/$2', $tshdaOptions);
$routes->post('(:locale)/hantana/(:segment)/apply', 'Hantana::apply/$1/$2', $tshdaOptions);

// FAQs (K).
$routes->get('(:locale)/faqs', 'Faqs::index/$1', $tshdaOptions);

// Feedback, queries and petitions (J.a.iii), with tracking by reference.
$routes->get('(:locale)/feedback', 'Feedback::index/$1', $tshdaOptions);
$routes->post('(:locale)/feedback', 'Feedback::submit/$1', $tshdaOptions);
$routes->get('(:locale)/feedback/track', 'Feedback::track/$1', $tshdaOptions);

// Moderated discussion (B.V, Clause 3.14).
$routes->get('(:locale)/discussion', 'Discussion::index/$1', $tshdaOptions);
$routes->get('(:locale)/discussion/(:segment)', 'Discussion::show/$1/$2', $tshdaOptions);
$routes->post('(:locale)/discussion/(:segment)', 'Discussion::comment/$1/$2', $tshdaOptions);

// Alert subscriptions (Clause 3.13), double opt-in.
$routes->post('(:locale)/alerts', 'Subscribe::create/$1', $tshdaOptions);
$routes->get('(:locale)/alerts/confirm/(:segment)', 'Subscribe::confirm/$1/$2', $tshdaOptions);
$routes->get('(:locale)/alerts/unsubscribe/(:segment)', 'Subscribe::unsubscribe/$1/$2', $tshdaOptions);

// Site-wide search (Clause 3.12).
$routes->get('(:locale)/search', 'Search::index/$1', $tshdaOptions);

// The help assistant's lookup. GET because it reads and changes nothing, which
// also means it needs no CSRF token to reach from the widget.
$routes->get('(:locale)/assistant/ask', 'Assistant::ask/$1', $tshdaOptions);

// Contact (J) and the human-readable sitemap (L).
$routes->get('(:locale)/contact', 'Contact::index/$1', $tshdaOptions);
$routes->get('(:locale)/sitemap', 'SitemapPage::index/$1', $tshdaOptions);

// Media gallery (I).
$routes->get('(:locale)/gallery', 'Gallery::photos/$1', $tshdaOptions);
$routes->get('(:locale)/videos', 'Gallery::videos/$1', $tshdaOptions);

// Vacancies (G): the listing here, the detail and the application in the
// Careers module which already has both.
$routes->get('(:locale)/vacancies', 'Vacancies::index/$1', $tshdaOptions);
$routes->get('(:locale)/vacancies/(:segment)', '\Modules\Careers\Controllers\Careers::show/$1/$2', ['filter' => 'applocale']);
$routes->post('(:locale)/vacancies/(:segment)/apply', '\Modules\Careers\Controllers\Careers::apply/$1/$2', ['filter' => 'applocale']);

// Newsroom and announcements (B.II, E.c). /announcements is the same listing
// filtered to the announcements category — a separate address because that is
// what the clause names and what the menu points at.
$routes->get('(:locale)/news', '\Modules\News\Controllers\News::index/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/announcements', '\Modules\News\Controllers\News::announcements/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/news/(:segment)', '\Modules\News\Controllers\News::show/$1/$2', ['filter' => 'applocale']);

// ── Field Officer Portal (Clause 3.1.II) ────────────────────────────────────
// The front door is public because it carries the sign-in form; everything
// behind it is gated by the officer session, never by the admin one.
$routes->get('(:locale)/field-officer', 'FieldOfficer::index/$1', $tshdaOptions);
$routes->post('(:locale)/field-officer/login', 'FieldOfficer::login/$1', $tshdaOptions);
$routes->get('(:locale)/field-officer/logout', 'FieldOfficer::logout/$1', $tshdaOptions);
$routes->group('(:locale)/field-officer', ['filter' => 'officerauth', 'namespace' => 'Modules\Tshda\Controllers'], static function ($routes) {
    $routes->get('dashboard', 'FieldOfficer::dashboard/$1');
    $routes->post('submit', 'FieldOfficer::submit/$1');
    $routes->get('attachment/(:segment)', 'FieldOfficer::attachment/$1/$2');
});

// CMS catch-all: /{locale}/{slug} -> PageController::show($slug)  ($2 = slug)
$routes->get('(:locale)/(:segment)', 'PageController::show/$2', $siteOptions);

// Anything that matched nothing at all. The framework's own 404 view is a
// standalone document with its own inline stylesheet, so it was the one page on
// the site that did not look like the site — shown at the moment a visitor is
// deciding whether to keep going. Routing it through a controller puts it back
// inside the normal request: layout, header, footer, theme and a way onward.
$routes->set404Override('Modules\Site\Controllers\NotFound::index');
