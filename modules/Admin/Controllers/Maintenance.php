<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Core\Libraries\Maintenance as MaintenanceMode;

/**
 * Admin -> Maintenance: the switch that takes the public site offline, the
 * copy shown while it is, and the preview link that lets a colleague or client
 * keep browsing the real site meanwhile.
 *
 * These routes stay reachable during maintenance (the filter always lets
 * /admin through), which is what makes it safe to switch on from here.
 */
class Maintenance extends BaseController
{
    /** Options this screen owns, and how each is cleaned before storing. */
    private const OPTIONS = ['brand', 'headline', 'message', 'until', 'allow_ips', 'retry_after'];

    public function index()
    {
        return view('Modules\Admin\Views\maintenance', [
            'title'     => 'Maintenance Mode',
            'active'    => 'maintenance',
            'state'     => MaintenanceMode::state(),
            'bypassKey' => MaintenanceMode::ensureBypassKey(),
            'yourIp'    => $this->request->getIPAddress(),
        ]);
    }

    /** Take the site offline / bring it back. */
    public function toggle()
    {
        $wanted = $this->request->getPost('state') === 'on';
        $user   = session()->get('admin_user')['email'] ?? 'admin';

        if ($wanted) {
            $ok = MaintenanceMode::enable((string) $user);

            return redirect()->to(site_url('admin/maintenance'))->with(
                $ok ? 'message' : 'error',
                $ok
                    ? 'Maintenance mode is ON — visitors now see the offline page.'
                    : 'Maintenance mode is ON, but writable/maintenance.flag could not be written. '
                        . 'Fix the permissions on writable/ so the switch survives a database outage.',
            );
        }

        $ok = MaintenanceMode::disable();

        return redirect()->to(site_url('admin/maintenance'))->with(
            $ok ? 'message' : 'error',
            $ok
                ? 'Maintenance mode is OFF — the site is live.'
                : 'Could not remove writable/maintenance.flag — the site is still offline. Delete that file on the server.',
        );
    }

    /** Save the offline-page copy and access options. */
    public function save()
    {
        foreach (self::OPTIONS as $option) {
            $value = trim((string) $this->request->getPost($option));

            if ($option === 'retry_after') {
                $value = (string) max(60, (int) ($value !== '' ? $value : MaintenanceMode::RETRY_AFTER));
            }

            MaintenanceMode::put($option, $value);
        }

        return redirect()->to(site_url('admin/maintenance'))->with('message', 'Maintenance settings saved.');
    }

    /** Issue a fresh preview key, invalidating the link already shared. */
    public function rotateKey()
    {
        MaintenanceMode::put('bypass_key', bin2hex(random_bytes(8)));

        return redirect()->to(site_url('admin/maintenance'))
            ->with('message', 'New preview link generated — the previous one no longer works.');
    }

    /** See the offline page exactly as a visitor would, without going offline. */
    public function preview()
    {
        return MaintenanceMode::render($this->request);
    }
}
