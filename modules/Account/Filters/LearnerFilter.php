<?php

namespace Modules\Account\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Modules\Account\Libraries\LearnerAuth;

/**
 * The gate on /account and the lesson player.
 *
 * Sends an unauthenticated visitor to the sign-in page and remembers where they
 * were going, because somebody who followed a link to their certificate should
 * land on their certificate after signing in and not on a dashboard they have
 * to navigate out of.
 */
class LearnerFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (LearnerAuth::check()) {
            return null;
        }

        helper('norlanka');
        session()->set('learner_intended', current_url());
        session()->setFlashdata('error', lang('Account.sign_in_required'));

        return redirect()->to(locale_url('account/login'));
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
