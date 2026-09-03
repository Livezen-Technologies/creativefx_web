<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Config\App;

/**
 * The welcome page (Clause 3.9 A): the trilingual entry choice at the root of
 * the site.
 *
 * A visitor who has been here before is sent straight through to the language
 * they chose last time — the entry page is a door, and a door you have already
 * walked through should not be shown to you again. The redirect is decided in
 * the browser, from the same stored preference the language switcher writes,
 * because the server cannot see it and a server-side redirect on a cached page
 * would send everyone to whoever loaded it first.
 */
class Welcome extends BaseController
{
    public function index()
    {
        helper('norlanka');

        $config = config(App::class);

        return view('Modules\Tshda\Views\welcome', [
            'locales'         => supported_locales(),
            'defaultLocale'   => $config->defaultLocale,
            'title'           => setting('site_name', 'Tea Small Holdings Development Authority'),
            'metaDescription' => 'Tea Small Holdings Development Authority — Sri Lanka. Choose English, සිංහල or தமிழ்.',
        ]);
    }
}
