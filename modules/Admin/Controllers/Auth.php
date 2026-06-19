<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Auth\Models\UserModel;

class Auth extends BaseController
{
    public function loginForm()
    {
        if (session()->get('admin_user')) {
            return redirect()->to(site_url('admin'));
        }
        return view('Modules\Admin\Views\login');
    }

    public function login()
    {
        $email    = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        $users = new UserModel();
        $user  = $users->findByEmail($email);

        if ($user === null
            || ! password_verify($password, $user['password_hash'])
            || ($user['status'] ?? 'active') !== 'active') {
            return redirect()->to(site_url('admin/login'))->with('error', 'Invalid credentials.');
        }

        session()->set('admin_user', [
            'id'    => (int) $user['id'],
            'email' => $user['email'],
            'name'  => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['email'],
            'roles' => $users->roleSlugs((int) $user['id']),
        ]);
        $users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return redirect()->to(site_url('admin'));
    }

    public function logout()
    {
        session()->remove('admin_user');
        return redirect()->to(site_url('admin/login'))->with('message', 'Signed out.');
    }
}
