<?php

namespace Modules\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Carry cookies set during a request onto a redirect.
 *
 * CodeIgniter's `redirect()` does not return the response the controller has
 * been writing to. It asks the container for a *second* object — a
 * RedirectResponse with its own, empty, cookie store — and that is the one the
 * framework sends. Every cookie set on `service('response')` before the
 * redirect is therefore thrown away without a word.
 *
 * That is not a hypothetical. It broke the shop:
 *
 *   POST /cart/add created the cart, wrote the row, held the seat, set the
 *   `mlp_cart` cookie and redirected to /cart with "added to your basket" —
 *   and /cart said the basket was empty, because the cookie naming the cart
 *   never left the server. Every request started a new cart. Nobody could buy
 *   anything, and the only symptom was an empty page after a success message.
 *
 * The framework's own remedy is `RedirectResponse::withCookies()`, which copies
 * the store across. Calling it at each redirect means every future controller
 * that touches a cookie has to remember to, at every exit, forever; the one
 * that forgets fails exactly as quietly as this did. So it is done once, here,
 * for every response the application sends.
 *
 * Registered as a global `after` filter in Config\Filters.
 */
class CarryCookiesFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Nothing to do on the way in.
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (! $response instanceof RedirectResponse) {
            return $response;
        }

        // Copies the cookie store from the shared Response service — the object
        // the controller actually set its cookies on.
        return $response->withCookies();
    }
}
