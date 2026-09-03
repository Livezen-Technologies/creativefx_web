<?php

namespace Modules\Analytics\Controllers;

use App\Controllers\BaseController;

/**
 * The one thing the server cannot see: a click that leaves the site.
 *
 * A WhatsApp button is an outbound link, so no page of ours is ever requested
 * and the page-view filter never runs. This endpoint exists for those, and for
 * nothing else — the event name is checked against a fixed list rather than
 * taken from the caller, so an open endpoint cannot be used to write arbitrary
 * rows into the analytics table.
 */
class Track extends BaseController
{
    private const ALLOWED = ['whatsapp_click', 'booking_open', 'film_play'];

    public function event()
    {
        $name = (string) $this->request->getPost('event');

        if (! in_array($name, self::ALLOWED, true)) {
            return $this->response->setStatusCode(204)->setBody('');
        }

        if (setting('enabled', '1', 'analytics') === '0'
            || $this->request->getServer('HTTP_DNT') === '1'
            || $this->request->getServer('HTTP_SEC_GPC') === '1') {
            return $this->response->setStatusCode(204)->setBody('');
        }

        try {
            // The same daily-rotating visitor hash the page-view filter uses, so
            // a click and the visit it came from are one visitor for a day and
            // unlinkable afterwards.
            $salt = date('Y-m-d') . '|' . (string) (config('Encryption')->key ?: config('App')->baseURL);

            model('Modules\Analytics\Models\AnalyticsModel')->insert([
                'event'      => $name,
                'path'       => substr((string) $this->request->getPost('path'), 0, 255),
                'locale'     => $this->request->getLocale(),
                'session_id' => substr(hash('sha256', $salt . '|' . $this->request->getIPAddress() . '|' . $this->request->getUserAgent()), 0, 40),
                'user_agent' => substr((string) $this->request->getUserAgent(), 0, 255),
            ]);
        } catch (\Throwable $e) {
            // Never worth an error on the page the visitor is leaving.
        }

        return $this->response->setStatusCode(204)->setBody('');
    }
}
