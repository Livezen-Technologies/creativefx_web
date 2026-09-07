<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Constrain the locale segment to the actual supported locales so the localized
// routes never shadow reserved prefixes like /admin or /api, regardless of the
// order in which module route files are discovered.
$routes->addPlaceholder('locale', implode('|', config('App')->supportedLocales));

$siteOptions     = ['filter' => 'applocale', 'namespace' => 'Modules\Site\Controllers'];
$catalogOptions  = ['filter' => 'applocale', 'namespace' => 'Modules\Catalog\Controllers'];
$commerceOptions = ['filter' => 'applocale', 'namespace' => 'Modules\Commerce\Controllers'];
$learningOptions = ['filter' => 'applocale', 'namespace' => 'Modules\Learning\Controllers'];
$accountOptions  = ['filter' => 'applocale', 'namespace' => 'Modules\Account\Controllers'];

// Every public route lives in this one file, in this one order, because the CMS
// catch-all at the bottom — /{locale}/{slug} — swallows anything declared after
// it and serves a 404 for a page that exists. Splitting these across module
// route files would make that ordering depend on the order namespaces happen to
// appear in Autoload.php, which is not something anybody should have to know.

// ── Outside the locale group ────────────────────────────────────────────────

// The root. A commercial site does not put a language chooser in front of its
// home page: the first-visit modal asks without blocking, and anybody arriving
// here goes straight on in the language their browser asked for.
$routes->get('/', 'Home::root', $siteOptions);

// Certificate verification. Deliberately unprefixed and deliberately public:
// this address is printed on a document and scanned from a QR square by an HR
// department that has never been to this site and has no interest in choosing a
// language first.
$routes->get('verify/(:segment)', 'Verify::show/$1', ['namespace' => 'Modules\Learning\Controllers']);

// Payment webhooks. No locale, no CSRF token — a gateway has neither — and
// exempted in Config\Filters. Each one verifies a signature before it believes
// a single field of what it was sent.
$routes->post('webhooks/(:segment)', 'Webhooks::receive/$1', ['namespace' => 'Modules\Commerce\Controllers']);

// Sitemaps, split by type as the SEO plan requires, and fetched by crawlers at
// fixed unprefixed addresses. Declared before the locale routes so nothing else
// can claim them.
$routes->get('sitemap.xml', 'Sitemap::index', ['namespace' => 'Modules\Site\Controllers']);
$routes->get('sitemap-(:segment).xml', 'Sitemap::section/$1', ['namespace' => 'Modules\Site\Controllers']);

// Outbound clicks the server cannot otherwise see.
$routes->post('api/track', 'Track::event', ['namespace' => 'Modules\Analytics\Controllers']);

// ── The localized site ──────────────────────────────────────────────────────

$routes->get('(:locale)', 'Home::index', $siteOptions);

// ── Catalogue ───────────────────────────────────────────────────────────────
// A course lives at /course/{slug} and a category at /courses/{slug}. The
// blueprint's sitemap puts both a sub-category and a course detail at
// /courses/{a}/{b}, which cannot be routed unambiguously — "photoshop" would be
// both a category and a possible course slug, and the router would have to
// guess. Category slugs are globally unique here, so one segment addresses any
// depth of the tree, and the breadcrumb still shows the whole path.
$routes->get('(:locale)/courses', 'Courses::index/$1', $catalogOptions);
$routes->get('(:locale)/courses/(:segment)', 'Courses::category/$1/$2', $catalogOptions);
$routes->get('(:locale)/course/(:segment)', 'Courses::show/$1/$2', $catalogOptions);

// The schedule. A session URL carries the course slug and ends in the id, so it
// reads as something — /schedule/photoshop-level-1-214 — and still resolves
// after the course is renamed.
$routes->get('(:locale)/schedule', 'Schedule::index/$1', $catalogOptions);
$routes->get('(:locale)/schedule/(:segment)', 'Schedule::show/$1/$2', $catalogOptions);

// Bundles: certificate programmes and bootcamps.
$routes->get('(:locale)/certificates', 'Bundles::certificates/$1', $catalogOptions);
$routes->get('(:locale)/certificates/(:segment)', 'Bundles::show/$1/$2', $catalogOptions);
$routes->get('(:locale)/bootcamps', 'Bundles::bootcamps/$1', $catalogOptions);
$routes->get('(:locale)/bootcamps/(:segment)', 'Bundles::show/$1/$2', $catalogOptions);

// The self-paced library.
$routes->get('(:locale)/on-demand', 'OnDemand::index/$1', $catalogOptions);
$routes->get('(:locale)/on-demand/(:segment)', 'OnDemand::show/$1/$2', $catalogOptions);

// The two pillar pages, and the certification hub beneath one of them. These
// are what the whole content plan links back to.
$routes->get('(:locale)/adobe', 'Pillars::adobe/$1', $catalogOptions);
$routes->get('(:locale)/adobe/certification', 'Pillars::certification/$1', $catalogOptions);
$routes->get('(:locale)/ai', 'Pillars::ai/$1', $catalogOptions);

// Local SEO landers, and the faculty.
$routes->get('(:locale)/locations', 'Locations::index/$1', $catalogOptions);
$routes->get('(:locale)/locations/(:segment)', 'Locations::show/$1/$2', $catalogOptions);
$routes->get('(:locale)/instructors', 'Instructors::index/$1', $catalogOptions);
$routes->get('(:locale)/instructors/(:segment)', 'Instructors::show/$1/$2', $catalogOptions);

// Reviews. Real ones only — nothing is seeded here, so at launch this page
// tells the truth about being new rather than showing invented praise.
$routes->get('(:locale)/reviews', 'Reviews::index/$1', $catalogOptions);
$routes->post('(:locale)/reviews', 'Reviews::submit/$1', $catalogOptions);

// Lead magnets and free sessions — the top of the funnel.
$routes->get('(:locale)/resources', 'Resources::index/$1', $catalogOptions);
$routes->get('(:locale)/resources/(:segment)', 'Resources::show/$1/$2', $catalogOptions);
$routes->post('(:locale)/resources/(:segment)', 'Resources::download/$1/$2', $catalogOptions);
$routes->get('(:locale)/webinars', 'Webinars::index/$1', $catalogOptions);
$routes->get('(:locale)/webinars/(:segment)', 'Webinars::show/$1/$2', $catalogOptions);

// Ask for a date that is not in the calendar. Every course page carries this,
// because "no date suits me" is otherwise a visitor who simply leaves.
$routes->post('(:locale)/waitlist', 'Schedule::waitlist/$1', $catalogOptions);

// ── Commerce ────────────────────────────────────────────────────────────────

// Public. A visitor has to see what a pass costs before deciding to make an
// account; the sign-in happens at checkout, where a pass needs somebody to
// belong to. Declared here rather than left to the catch-all page route at the
// bottom of this file, which would send /membership to a CMS page that has
// never existed and answer 404.
$routes->get('(:locale)/membership', 'Membership::index/$1', $commerceOptions);

$routes->get('(:locale)/cart', 'Cart::index/$1', $commerceOptions);
$routes->post('(:locale)/cart/add', 'Cart::add/$1', $commerceOptions);
$routes->post('(:locale)/cart/update', 'Cart::update/$1', $commerceOptions);
$routes->post('(:locale)/cart/remove', 'Cart::remove/$1', $commerceOptions);
$routes->post('(:locale)/cart/coupon', 'Cart::coupon/$1', $commerceOptions);

$routes->get('(:locale)/checkout', 'Checkout::index/$1', $commerceOptions);
$routes->post('(:locale)/checkout', 'Checkout::place/$1', $commerceOptions);
$routes->get('(:locale)/checkout/pay/(:segment)', 'Checkout::pay/$1/$2', $commerceOptions);
// The buyer's return from a gateway. It confirms nothing: the order is shown as
// it stands, which after a webhook has landed is paid and before one has is "we
// are waiting for your bank". Believing this redirect is how a site enrols
// somebody who never paid.
$routes->get('(:locale)/checkout/return/(:segment)', 'Checkout::returned/$1/$2', $commerceOptions);
$routes->get('(:locale)/order/(:segment)', 'Checkout::order/$1/$2', $commerceOptions);
$routes->get('(:locale)/order/(:segment)/invoice', 'Checkout::invoice/$1/$2', $commerceOptions);

// An explicit currency choice, which outranks every guess afterwards.
$routes->get('(:locale)/currency/(:segment)', 'Currency::set/$1/$2', $commerceOptions);

// The corporate funnel: a landing page and a request for a quote, never a
// checkout. A company buying training for twelve people wants a conversation
// and an invoice, not a card form.
$routes->get('(:locale)/corporate', 'Corporate::index/$1', $commerceOptions);
$routes->get('(:locale)/corporate/request-quote', 'Corporate::request/$1', $commerceOptions);
$routes->post('(:locale)/corporate/request-quote', 'Corporate::submit/$1', $commerceOptions);

// ── The learner's account ───────────────────────────────────────────────────
// The front door is public because it carries the sign-in and registration
// forms; everything behind it is gated by the learner session, never by the
// admin one.
$routes->get('(:locale)/account/login', 'Auth::loginForm/$1', $accountOptions);
$routes->post('(:locale)/account/login', 'Auth::login/$1', $accountOptions);
$routes->get('(:locale)/account/register', 'Auth::registerForm/$1', $accountOptions);
$routes->post('(:locale)/account/register', 'Auth::register/$1', $accountOptions);
$routes->get('(:locale)/account/verify/(:segment)', 'Auth::verify/$1/$2', $accountOptions);
$routes->get('(:locale)/account/forgot', 'Auth::forgotForm/$1', $accountOptions);
$routes->post('(:locale)/account/forgot', 'Auth::forgot/$1', $accountOptions);
$routes->get('(:locale)/account/reset/(:segment)', 'Auth::resetForm/$1/$2', $accountOptions);
$routes->post('(:locale)/account/reset/(:segment)', 'Auth::reset/$1/$2', $accountOptions);
$routes->get('(:locale)/account/logout', 'Auth::logout/$1', $accountOptions);

// Two filters, not one. `learner` proves who is asking; `applocale` is the only
// thing in the codebase that calls $request->setLocale(), and it is not global.
// Without it every page inside /si/account renders in whatever language
// Accept-Language negotiated — so a Sinhala reader signs in and lands on an
// English dashboard, with nothing in the log to say why.
$routes->group('(:locale)/account', ['filter' => ['applocale', 'learner'], 'namespace' => 'Modules\Account\Controllers'], static function ($routes) {
    $routes->get('', 'Dashboard::index/$1');
    $routes->get('courses', 'Dashboard::courses/$1');
    $routes->get('live/(:num)', 'Dashboard::live/$1/$2');
    $routes->get('certificates', 'Dashboard::certificates/$1');
    $routes->get('certificates/(:num)/download', 'Dashboard::certificate/$1/$2');
    $routes->get('invoices', 'Dashboard::invoices/$1');
    $routes->get('invoices/(:num)', 'Dashboard::invoice/$1/$2');
    $routes->get('profile', 'Dashboard::profile/$1');
    $routes->post('profile', 'Dashboard::saveProfile/$1');
    $routes->post('transfer', 'Dashboard::requestTransfer/$1');
});

// The self-paced player. Gated by the same filter, plus a check inside that
// this learner actually holds an enrolment on this course — a signed-in visitor
// is not the same thing as a paying one.
$routes->group('(:locale)/learn', ['filter' => ['applocale', 'learner'], 'namespace' => 'Modules\Learning\Controllers'], static function ($routes) {
    $routes->get('(:segment)', 'Player::course/$1/$2');
    $routes->get('(:segment)/(:segment)', 'Player::lesson/$1/$2/$3');
    $routes->post('(:segment)/(:segment)/progress', 'Player::progress/$1/$2/$3');
    $routes->post('(:segment)/(:segment)/quiz', 'Player::quiz/$1/$2/$3');
    $routes->get('(:segment)/asset/(:num)', 'Player::asset/$1/$2/$3');
});

// A free preview lesson, open to anybody. The biggest single conversion lever on
// the on-demand catalogue, and its transcript is a page a search engine can read.
$routes->get('(:locale)/preview/(:segment)/(:segment)', 'Player::preview/$1/$2/$3', $learningOptions);

// ── Search, contact and the human sitemap ───────────────────────────────────
$routes->get('(:locale)/search', 'Search::index/$1', $siteOptions);
$routes->post('(:locale)/contact', 'Contact::submit/$1', $siteOptions);
$routes->post('(:locale)/subscribe', 'Contact::subscribe/$1', $siteOptions);
$routes->get('(:locale)/sitemap', 'Sitemap::page/$1', $siteOptions);

// The help assistant's lookup. GET because it reads and changes nothing, which
// also means it needs no CSRF token to reach from the widget.
$routes->get('(:locale)/assistant/ask', 'Assistant::ask/$1', $siteOptions);

// The blog. The News module already has listing, category and detail; this
// mounts it at the address the content plan links to.
$routes->get('(:locale)/blog', '\Modules\News\Controllers\News::index/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/blog/category/(:segment)', '\Modules\News\Controllers\News::category/$1/$2', ['filter' => 'applocale']);
$routes->get('(:locale)/blog/(:segment)', '\Modules\News\Controllers\News::show/$1/$2', ['filter' => 'applocale']);

// Vacancies at the school itself.
$routes->get('(:locale)/careers', '\Modules\Careers\Controllers\Careers::index/$1', ['filter' => 'applocale']);
$routes->get('(:locale)/careers/(:segment)', '\Modules\Careers\Controllers\Careers::show/$1/$2', ['filter' => 'applocale']);
$routes->post('(:locale)/careers/(:segment)/apply', '\Modules\Careers\Controllers\Careers::apply/$1/$2', ['filter' => 'applocale']);

// ── The CMS catch-all, which must stay last ─────────────────────────────────
// /{locale}/{slug} -> PageController::show($slug). About, why-mylearnplus, faq,
// the policies, contact — everything an editor can create without a deployment.
$routes->get('(:locale)/(:segment)', 'PageController::show/$2', $siteOptions);

// Anything that matched nothing at all. The framework's own 404 view is a
// standalone document with its own inline stylesheet, so it was the one page on
// the site that did not look like the site — shown at the moment a visitor is
// deciding whether to keep going. Routing it through a controller puts it back
// inside the normal request: layout, header, footer, theme and a way onward.
$routes->set404Override('Modules\Site\Controllers\NotFound::index');
