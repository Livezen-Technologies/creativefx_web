<?php

namespace Modules\Auth\Controllers\Api;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Modules\Auth\Libraries\Jwt;
use Modules\Auth\Models\UserModel;

/**
 * Authentication API. The login endpoint verifies credentials against the
 * `users` table and returns a JWT. This is the foundation for the admin/API
 * auth flow — full RBAC enforcement is layered on later.
 */
class AuthController extends ResourceController
{
    use ResponseTrait;

    public function login()
    {
        $email    = (string) $this->request->getVar('email');
        $password = (string) $this->request->getVar('password');

        if ($email === '' || $password === '') {
            return $this->failValidationErrors('Email and password are required.');
        }

        $users = new UserModel();
        $user  = $users->findByEmail($email);

        if ($user === null || ! password_verify($password, $user['password_hash'])) {
            return $this->failUnauthorized('Invalid credentials.');
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return $this->failForbidden('Account is not active.');
        }

        $roles = $users->roleSlugs((int) $user['id']);
        $token = (new Jwt())->issue((int) $user['id'], [
            'email' => $user['email'],
            'roles' => $roles,
        ]);

        $users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return $this->respond([
            'status' => 'success',
            'token'  => $token,
            'user'   => [
                'id'    => (int) $user['id'],
                'email' => $user['email'],
                'name'  => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
                'roles' => $roles,
            ],
        ]);
    }

    /** Returns the identity behind the current bearer token. */
    public function me()
    {
        $claims = \Modules\Auth\Libraries\AuthContext::claims();
        if ($claims === null) {
            return $this->failUnauthorized();
        }

        return $this->respond([
            'status' => 'success',
            'user'   => [
                'id'    => $claims->sub ?? null,
                'email' => $claims->email ?? null,
                'roles' => $claims->roles ?? [],
            ],
        ]);
    }
}
