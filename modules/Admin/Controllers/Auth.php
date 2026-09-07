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

        // A correct password is not, on its own, permission to be here.
        //
        // This check did not exist while `users` held nothing but staff. It has
        // to exist now: MyLearnPlus lets customers register themselves, every
        // one of them lands in the same table, and without this line any
        // learner who bought a course could sign into the back office with the
        // password they chose at checkout. The same wording as a wrong password
        // on purpose — whether an address is a staff account is not something
        // this form should answer.
        if (! $users->isStaff((int) $user['id'])) {
            return redirect()->to(site_url('admin/login'))->with('error', 'Invalid credentials.');
        }

        // A new session id at the moment privilege changes. Without it, a
        // session id captured before sign-in — from a shared machine, a link,
        // an XSS on any page — is still valid afterwards, and is now an
        // administrator's.
        session()->regenerate(true);

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
