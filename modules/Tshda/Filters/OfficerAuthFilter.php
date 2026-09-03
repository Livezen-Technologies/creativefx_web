<?php

namespace Modules\Tshda\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The gate on the Field Officer Portal.
 *
 * A separate session key from the admin console, deliberately. An officer in
 * the field is not an administrator, and a single session that opened both
 * would mean the portal's much larger and less controlled login population
 * carried a key to the console. Signing in to one does not sign you in to the
 * other.
 */
class OfficerAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $officer = session()->get('field_officer');
        if (! is_array($officer) || empty($officer['id'])) {
            helper('norlanka');
            session()->setFlashdata('officer_error', lang('Site.officer.sign_in_required'));
            // Where they were going, so the login can send them on afterwards
            // rather than dumping them on the portal's front page.
            session()->set('officer_intended', current_url());

            return redirect()->to(locale_url('field-officer'));
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
