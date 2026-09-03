<?php

namespace Modules\Analytics\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * First-party page-view recording.
 *
 * The analytics table has existed since the first migration and nothing has
 * ever written to it, so every figure the admin could show was zero. This fills
 * it, from the server, without a script in the page and without a third party.
 *
 * What is deliberately not stored:
 *
 *   - No IP address. A visitor is counted by a hash of their address, their
 *     user agent and a salt that changes every day, so the same person is one
 *     visitor within a day and unrecoverable afterwards. Nothing here can be
 *     turned back into a person or followed between days.
 *   - No query string. It carries search terms, booking dates and campaign
 *     ids, and the path alone answers every question this dashboard asks.
 *   - No referrer path. Only the host, which is what "where did they come
 *     from" means; the full URL of the page someone was reading before is
 *     theirs, not the hotel's.
 *
 * Honoured before anything is written: the site's own analytics switch, Do Not
 * Track, and Global Privacy Control. A request that asks not to be measured is
 * not measured, rather than measured and excluded later.
 */
class TrackPageView implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        try {
            if (! $this->shouldRecord($request, $response)) {
                return null;
            }

            model('Modules\Analytics\Models\AnalyticsModel')->insert([
                'event'      => 'pageview',
                'path'       => $this->path($request),
                'locale'     => $request->getLocale(),
                'session_id' => $this->visitorHash($request),
                'referrer'   => $this->referrerHost($request),
                'user_agent' => substr((string) $request->getUserAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Measurement must never be the reason a page fails to load.
        }

        return null;
    }

    private function shouldRecord(RequestInterface $request, ResponseInterface $response): bool
    {
        if (! $request instanceof \CodeIgniter\HTTP\IncomingRequest) {
            return false;
        }
        if ($request->getMethod() !== 'GET') {
            return false;
        }
        if ($response->getStatusCode() >= 300) {
            return false;
        }
        // Only real pages: not assets, not JSON, not the admin.
        if (! str_contains((string) $response->getHeaderLine('Content-Type'), 'text/html')) {
            return false;
        }

        $path = trim($request->getUri()->getPath(), '/');
        foreach (['admin', 'api', 'build', 'media', 'sitemap.xml'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return false;
            }
        }

        if (setting('enabled', '1', 'analytics') === '0') {
            return false;
        }

        // Do Not Track and Global Privacy Control. Both are a request not to be
        // measured, and both are cheap to honour.
        if ($request->getServer('HTTP_DNT') === '1' || $request->getServer('HTTP_SEC_GPC') === '1') {
            return false;
        }

        return ! $this->isBot((string) $request->getUserAgent());
    }

    /** The path without its query string, capped so a long URL cannot fill the column. */
    private function path(RequestInterface $request): string
    {
        $path = '/' . trim($request->getUri()->getPath(), '/');

        return substr($path === '/' ? '/' : $path, 0, 255);
    }

    /**
     * A visitor identifier that cannot be reversed and does not survive the day.
     *
     * The salt is the date plus the app's own key, so two runs on the same day
     * agree and yesterday's hashes cannot be recomputed to match today's.
     */
    private function visitorHash(RequestInterface $request): string
    {
        $salt = date('Y-m-d') . '|' . (string) (config('Encryption')->key ?: config('App')->baseURL);

        return substr(hash('sha256', $salt . '|' . $request->getIPAddress() . '|' . $request->getUserAgent()), 0, 40);
    }

    /** The host somebody arrived from, or null for a direct visit or our own pages. */
    private function referrerHost(RequestInterface $request): ?string
    {
        $referrer = (string) $request->getServer('HTTP_REFERER');
        if ($referrer === '') {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        $ourHost = parse_url((string) config('App')->baseURL, PHP_URL_HOST);

        return $host === $ourHost ? null : substr($host, 0, 191);
    }

    private function isBot(string $agent): bool
    {
        if ($agent === '') {
            return true;
        }

        return (bool) preg_match(
            '~bot|crawl|spider|slurp|bing|yandex|baidu|duckduck|facebookexternalhit|headless|lighthouse|preview|monitor|curl|wget|python-requests|axios|postman~i',
            $agent,
        );
    }
}
