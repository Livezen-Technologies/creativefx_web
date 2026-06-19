<?php

namespace Modules\Auth\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Auth\Libraries\AuthContext;
use Modules\Auth\Libraries\Jwt;

/**
 * JwtAuthFilter — guards /api and /admin. Reads a Bearer token, verifies it,
 * and stashes the decoded payload on the request. On failure:
 *   - /api*   -> 401 JSON
 *   - /admin* -> redirect to the (stub) admin login
 */
class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');
        $token  = '';

        if (preg_match('/Bearer\s+(.+)$/i', $header, $m)) {
            $token = trim($m[1]);
        }

        $payload = $token !== '' ? (new Jwt())->decode($token) : null;

        if ($payload === null) {
            $path = $request->getUri()->getPath();

            if (str_starts_with(ltrim($path, '/'), 'api')) {
                return service('response')
                    ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                    ->setJSON(['status' => 'error', 'message' => 'Unauthorized']);
            }

            return redirect()->to('/admin/login');
        }

        // Make the authenticated user id available downstream.
        AuthContext::set(isset($payload->sub) ? (int) $payload->sub : null, $payload);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
