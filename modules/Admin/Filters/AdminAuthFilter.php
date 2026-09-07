<?php

namespace Modules\Admin\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Session-based gate for the admin panel. Browser navigation cannot carry a
 * Bearer header, so the console uses a server session; the JWT filter still
 * guards /api for programmatic access.
 *
 * It checks two things, not one. "Is somebody signed in" was enough while
 * `users` held only staff. It stopped being enough the moment customers could
 * register themselves into the same table, so this also asserts a staff role on
 * every request — both because a customer must never reach the console, and
 * because a role revoked mid-session should take effect on the next click
 * rather than at the next sign-out.
 */
class AdminAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $admin = session()->get('admin_user');

        if (! $admin) {
            session()->setFlashdata('error', 'Please sign in to continue.');

            return redirect()->to(site_url('admin/login'));
        }

        // The session existing is not the same as the session being allowed
        // here. The sign-in already refuses anybody without a staff role, but a
        // session outlives the check that created it: a member of staff whose
        // role is removed while they are signed in would otherwise keep the
        // console until they happened to sign out. Re-asserting it on every
        // request is cheap and is what makes "remove their role" actually mean
        // something.
        $roles = $admin['roles'] ?? [];
        if (array_intersect($roles, \Modules\Auth\Database\Seeds\RoleSeeder::STAFF) === []) {
            session()->remove('admin_user');
            session()->setFlashdata('error', 'That account does not have access to the console.');

            return redirect()->to(site_url('admin/login'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
