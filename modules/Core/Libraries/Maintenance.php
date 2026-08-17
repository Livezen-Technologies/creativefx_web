<?php

namespace Modules\Core\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use Modules\Core\Models\SettingModel;
use Throwable;

/**
 * Maintenance mode — takes the public site off the air while work happens,
 * without touching Nginx or the deployment.
 *
 * Three levers, read in this order, so the site can always be taken down (or
 * brought back) even when the layer below it is broken:
 *
 *   1. `maintenance.enabled` in `.env` — an ops override that wins outright.
 *   2. `writable/maintenance.flag`     — a file, so it still works with the
 *      database down; untracked, so `git reset --hard` during a deploy
 *      leaves it alone.
 *   3. the `maintenance.enabled` settings row — what Admin -> Maintenance and
 *      `php spark maintenance` write.
 *
 * enable()/disable() write the flag file *and* the settings row together, so
 * the two levers never end up disagreeing about whether the site is up.
 *
 * Nothing here may throw when the database is unreachable: a broken database
 * is one of the reasons to be in maintenance in the first place. Every DB
 * read goes through setting() (which swallows its own errors) and every write
 * is wrapped.
 */
class Maintenance
{
    /** Settings group that holds every maintenance option. */
    public const GROUP = 'maintenance';

    /** Query parameter that unlocks the site, and the cookie it leaves behind. */
    public const BYPASS_QUERY  = 'preview';
    public const BYPASS_COOKIE = 'nl_maintenance_bypass';

    /** Default Retry-After, in seconds, sent with the 503. */
    public const RETRY_AFTER = 3600;

    /**
     * Shipped copy, per locale, used until someone writes their own in
     * Admin -> Maintenance. Kept here rather than in a language file so the
     * page still speaks four languages with the database down.
     *
     * @var array<string, array{0:string, 1:string}> [headline, message]
     */
    public const DEFAULT_COPY = [
        'en' => [
            'We will be back shortly',
            'Our site is briefly offline while we make some improvements. Thank you for your patience — please check back in a little while.',
        ],
        'ja' => [
            'まもなく再開いたします',
            'ただいまサイトのメンテナンスを行っております。ご不便をおかけしますが、しばらく経ってから再度アクセスしてください。',
        ],
        'es' => [
            'Volvemos enseguida',
            'Nuestro sitio está fuera de línea temporalmente mientras hacemos algunas mejoras. Gracias por su paciencia: vuelva a intentarlo dentro de un rato.',
        ],
        'zh' => [
            '我们很快回来',
            '网站正在进行维护升级，暂时无法访问。感谢您的耐心等待，请稍后再试。',
        ],
    ];

    /** Absolute path of the flag file. */
    public static function flagPath(): string
    {
        return WRITEPATH . 'maintenance.flag';
    }

    /** Is the public site currently held offline? */
    public static function isActive(): bool
    {
        $override = env('maintenance.enabled');
        if ($override !== null && $override !== '') {
            return self::truthy($override);
        }

        if (is_file(self::flagPath())) {
            return true;
        }

        return self::truthy(self::option('enabled', '0'));
    }

    /**
     * Take the site offline. Returns false when the flag file could not be
     * written — maintenance is still on via the settings row, but the caller
     * should say so, because the file is the lever that survives a DB outage.
     */
    public static function enable(?string $by = null): bool
    {
        $written = @file_put_contents(self::flagPath(), json_encode([
            'enabled' => true,
            'since'   => date('c'),
            'by'      => $by,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

        self::put('enabled', '1');
        self::put('since', date('Y-m-d H:i:s'));

        return $written !== false;
    }

    /** Bring the site back. Returns false when the flag file could not be removed. */
    public static function disable(): bool
    {
        $removed = is_file(self::flagPath()) ? @unlink(self::flagPath()) : true;

        self::put('enabled', '0');

        return $removed;
    }

    /**
     * Everything the admin screen needs to describe the current state.
     *
     * @return array{active:bool, flag:bool, env:bool, since:string, until:string,
     *               brand:string, headline:string, message:string, bypass_key:string,
     *               allow_ips:string, retry_after:int}
     */
    public static function state(): array
    {
        $override = env('maintenance.enabled');

        return [
            'active'      => self::isActive(),
            'flag'        => is_file(self::flagPath()),
            'env'         => $override !== null && $override !== '',
            'since'       => (string) self::option('since', ''),
            'brand'       => (string) self::option('brand', ''),
            'until'       => (string) self::option('until', ''),
            'headline'    => (string) self::option('headline', ''),
            'message'     => (string) self::option('message', ''),
            'bypass_key'  => (string) self::option('bypass_key', ''),
            'allow_ips'   => (string) self::option('allow_ips', ''),
            'retry_after' => (int) self::option('retry_after', self::RETRY_AFTER),
        ];
    }

    /**
     * May this request through while the site is down?
     *
     * The admin panel is always reachable — it is where the switch lives, and
     * locking yourself out of it would mean SSHing in to get back up.
     */
    public static function allows(RequestInterface $request): bool
    {
        $path = self::path($request);

        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            return true;
        }

        if (self::isSignedInAdmin()) {
            return true;
        }

        if (self::ipAllowed((string) $request->getIPAddress())) {
            return true;
        }

        $key = (string) self::option('bypass_key', '');
        if ($key !== '') {
            $cookie = $_COOKIE[self::BYPASS_COOKIE] ?? '';
            if (is_string($cookie) && $cookie !== '' && hash_equals($key, $cookie)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The bypass key supplied on this request's query string, if it is the
     * right one. The filter turns this into a cookie so the rest of the visit
     * works without dragging the key through every URL.
     */
    public static function bypassOffered(RequestInterface $request): ?string
    {
        $key = (string) self::option('bypass_key', '');
        if ($key === '' || ! $request instanceof IncomingRequest) {
            return null;
        }

        $offered = $request->getGet(self::BYPASS_QUERY);

        return is_string($offered) && hash_equals($key, $offered) ? $key : null;
    }

    /** Generate and persist a bypass key (leaves an existing one alone). */
    public static function ensureBypassKey(): string
    {
        $key = (string) self::option('bypass_key', '');
        if ($key === '') {
            $key = bin2hex(random_bytes(8));
            self::put('bypass_key', $key);
        }

        return $key;
    }

    /** Write one option to the settings table. */
    public static function put(string $key, string $value): void
    {
        try {
            $model = model(SettingModel::class);
            $row   = $model->where('group', self::GROUP)->where('key', $key)->first();

            if ($row === null) {
                $model->insert([
                    'group'     => self::GROUP,
                    'key'       => $key,
                    'value'     => $value,
                    'type'      => 'string',
                    'is_public' => 0,
                ]);
            } else {
                $model->update($row['id'], ['value' => $value]);
            }
        } catch (Throwable $e) {
            // A dead database must not stop us switching maintenance on.
            log_message('error', 'Maintenance: could not save "{key}": {msg}', [
                'key' => $key,
                'msg' => $e->getMessage(),
            ]);
        }
    }

    /** Read one option from the settings table. */
    public static function option(string $key, $default = null)
    {
        helper('norlanka');

        return setting($key, $default, self::GROUP);
    }

    /**
     * Which language the offline page should speak: ?lang= wins, then the
     * locale segment of the URL they asked for, then the cookie the locale
     * filter left on an earlier visit.
     */
    public static function locale(RequestInterface $request): string
    {
        $supported = config('App')->supportedLocales;
        $default   = config('App')->defaultLocale;

        $candidates = [];
        if ($request instanceof IncomingRequest) {
            $candidates[] = $request->getGet('lang');
        }
        $candidates[] = explode('/', self::path($request))[0] ?? '';
        $candidates[] = $_COOKIE['locale'] ?? '';

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return $default;
    }

    /**
     * Render the offline page.
     *
     * Deliberately standalone: no layout, no Vite manifest, no database
     * required. Whatever is broken, this page still draws.
     */
    public static function render(RequestInterface $request): string
    {
        helper('norlanka');

        $locale = self::locale($request);
        [$headline, $message] = self::DEFAULT_COPY[$locale] ?? self::DEFAULT_COPY['en'];

        return view('Modules\Core\Views\maintenance', [
            'locale'   => $locale,
            'brand'    => (string) self::option('brand', '') ?: (string) setting('site_name', 'Norlanka'),
            'headline' => (string) self::option('headline', '') ?: $headline,
            'message'  => (string) self::option('message', '') ?: $message,
            'until'    => (string) self::option('until', ''),
            'email'    => (string) setting('email', '', 'contact'),
            'locales'  => config('App')->supportedLocales,
        ]);
    }

    /** Does this request want JSON back rather than a page? */
    public static function wantsJson(RequestInterface $request): bool
    {
        if (str_starts_with(self::path($request), 'api/')) {
            return true;
        }

        if (! $request instanceof IncomingRequest) {
            return false;
        }

        return $request->isAJAX()
            || str_contains(strtolower((string) $request->getHeaderLine('Accept')), 'application/json');
    }

    /** The request path, relative to the app root and without slashes at the ends. */
    private static function path(RequestInterface $request): string
    {
        $path = $request instanceof IncomingRequest
            ? $request->getPath()
            : $request->getUri()->getPath();

        return trim($path, '/');
    }

    /**
     * Is an admin signed in? Checked only when a session cookie is actually
     * present, so an outage does not mint a session for every passing visitor
     * (and every bot) that hits the offline page.
     */
    private static function isSignedInAdmin(): bool
    {
        $name = config('Session')->cookieName ?? 'ci_session';

        if (! isset($_COOKIE[$name])) {
            return false;
        }

        try {
            return (bool) session()->get('admin_user');
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Is this address on the allowlist? Accepts comma/space separated entries. */
    private static function ipAllowed(string $ip): bool
    {
        $list = (string) self::option('allow_ips', '');
        if ($ip === '' || trim($list) === '') {
            return false;
        }

        foreach (preg_split('/[\s,]+/', $list, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $allowed) {
            if (strcasecmp($allowed, $ip) === 0) {
                return true;
            }
        }

        return false;
    }

    /** Read a stored/env flag as a boolean ('1', 'true', 'on', 'yes' are all on). */
    private static function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var((string) $value, FILTER_VALIDATE_BOOLEAN);
    }
}
