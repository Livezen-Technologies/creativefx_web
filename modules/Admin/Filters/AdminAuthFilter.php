<?php

namespace Modules\Admin\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Session-based gate for the admin panel. Browser navigation can't carry a
 * Bearer header, so the admin UI uses a server session (the JWT filter still
 * guards the /api endpoints for programmatic access).
 */
class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! session()->get('admin_user')) {
            session()->setFlashdata('error', 'Please sign in to continue.');
            return redirect()->to(site_url('admin/login'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
