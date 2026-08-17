<?php

namespace Modules\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Core\Libraries\Maintenance;

/**
 * Holds the public site at a 503 while maintenance mode is on.
 *
 * Registered in Config\Filters::$required so it runs ahead of everything else
 * and on *every* request — including URLs that match no route, which would
 * otherwise leak the 404 page while the site is supposed to be dark.
 *
 * @see Maintenance for the switch itself and the bypass rules.
 */
class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest || ! Maintenance::isActive()) {
            return null;
        }

        // Arriving with the right ?preview= key: swap it for a cookie and bounce
        // to the clean URL, so the key stops travelling in the address bar,
        // browser history and outbound referrers.
        $key = Maintenance::bypassOffered($request);
        if ($key !== null) {
            $uri = clone $request->getUri();
            $uri->stripQuery(Maintenance::BYPASS_QUERY);

            return redirect()->to((string) $uri)->setCookie(
                Maintenance::BYPASS_COOKIE,
                $key,
                DAY,
                '',
                '/',
                '',
                null,
                true,
                'Lax',
            );
        }

        if (Maintenance::allows($request)) {
            return null;
        }

        return $this->offline($request);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    /**
     * The 503 itself.
     *
     * 503 + Retry-After is what tells crawlers this is temporary — they hold
     * the pages and come back, rather than dropping them from the index. That
     * is also why the page carries no `noindex`: it would invite exactly the
     * deindexing the 503 exists to prevent.
     */
    private function offline(IncomingRequest $request): ResponseInterface
    {
        $retryAfter = max(1, (int) Maintenance::option('retry_after', Maintenance::RETRY_AFTER));

        $response = service('response')
            ->setStatusCode(503, 'Service Unavailable')
            ->setHeader('Retry-After', (string) $retryAfter)
            // Nothing may cache the offline page — not the browser, not
            // Cloudflare. Coming back up has to be visible on the next request.
            // noCache() rather than setHeader(): setHeader() *appends* to an
            // array-valued header, and Cache-Control already is one.
            ->noCache();

        if (Maintenance::wantsJson($request)) {
            return $response->setJSON([
                'status'  => 503,
                'error'   => 'maintenance',
                'message' => 'The service is temporarily unavailable for maintenance.',
            ]);
        }

        return $response
            ->setContentType('text/html; charset=UTF-8')
            ->setBody(Maintenance::render($request));
    }
}
