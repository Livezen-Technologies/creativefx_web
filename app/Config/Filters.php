<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;
use Modules\Admin\Filters\AdminAuthFilter;
use Modules\Auth\Filters\JwtAuthFilter;
use Modules\Core\Filters\LocaleFilter;
use Modules\Analytics\Filters\TrackPageView;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,

        // Site modules.
        // NB: 'applocale' (not 'locale') — CI4's enableFilter prefers an existing
        // class via class_exists(), and PHP's built-in \Locale would shadow a
        // 'locale' alias because class names are case-insensitive.
        'jwt'           => JwtAuthFilter::class,
        'applocale'     => LocaleFilter::class,
        'adminauth'     => AdminAuthFilter::class,
        'trackview'     => TrackPageView::class,
        // The learner's session, kept separate from the administrator's. They
        // are different populations with different powers, and one session key
        // for both is how a bug in the shop becomes a way into the console.
        'learner'       => \Modules\Account\Filters\LearnerFilter::class,
        'carrycookies'  => \Modules\Core\Filters\CarryCookiesFilter::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            // First, before anything can replace the response: a redirect is a
            // fresh object with an empty cookie store, so cookies set during
            // the request have to be copied onto it or they are dropped. See
            // CarryCookiesFilter — this is why the basket used to come back
            // empty straight after "added to your basket".
            'carrycookies',
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            'toolbar',     // Debug Toolbar
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            // CSRF on every state-changing request, not only the admin's.
            //
            // The console was the only thing protected while the public side
            // was a contact form: a forged POST could send the office an email,
            // which is annoying rather than dangerous. That stopped being true
            // the moment this site grew a cart, a checkout, a profile and a
            // review form — a cross-site POST can now put items in somebody's
            // basket, place an order in their name, or change the address a
            // certificate is issued to. So the rule is inverted: everything is
            // protected, and the two things that genuinely cannot carry a token
            // are named.
            //
            // It lives in $globals rather than $filters because only $globals
            // honours `except` — processFilters() reads nothing but `before`
            // and `after`, so an exception declared there is silently ignored
            // and the webhook endpoints start rejecting every gateway that
            // calls them.
            //
            // Security::verify() ignores GET, HEAD and OPTIONS outright, so
            // this costs nothing on the pages people read.
            //
            //   webhooks/* — a payment gateway has no session and no token, and
            //                each handler verifies a cryptographic signature
            //                instead, which is the stronger check.
            //   api/*      — the JSON API authenticates with a bearer token,
            //                which a browser cannot attach cross-site anyway.
            'csrf' => ['except' => ['webhooks/*', 'api/*']],
            // 'honeypot',
            // 'invalidchars',
        ],
        'after' => [
            // Records a page view for public HTML responses. The filter decides
            // for itself what to skip — the admin, assets, redirects, bots, and
            // any request carrying Do Not Track — so it is registered globally
            // rather than route by route, where a new page would be missed.
            'trackview',
            // 'honeypot',
            // 'secureheaders',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        // CSRF is declared in $globals above, not here: this array's handler
        // reads only `before` and `after`, so an `except` written here would be
        // ignored without a word and the payment webhooks would start refusing
        // every gateway that called them.
    ];
}
