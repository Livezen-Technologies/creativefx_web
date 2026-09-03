<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use Config\App;
use Config\Services;

/**
 * The 404 page.
 *
 * CodeIgniter's stock 404 view is a standalone HTML document with its own
 * inline stylesheet — grey Helvetica on off-white, an orange link colour, and
 * nothing else. It is the one page on the site that did not look like the site,
 * shown at exactly the moment a visitor is deciding whether the site is worth
 * more of their time.
 *
 * Routing 404s through a controller instead of the error view puts the page
 * back inside the normal request: the layout, the header, the footer, the
 * booking dialog and the theme all apply, and it can offer somewhere to go.
 *
 * A missing page is still a missing page, so the response carries a real 404.
 * Anything else tells a crawler the URL is fine and gets it indexed.
 */
class NotFound extends BaseController
{
    public function index()
    {
        // A 404 can land on any URL, including ones with no locale segment or a
        // nonsense one, and every link on the page is built with locale_url().
        // Without this they would all be built against whatever the request
        // filter last managed to set.
        $app       = config(App::class);
        $requested = explode('/', trim(uri_string(), '/'))[0] ?? '';
        $locale    = in_array($requested, $app->supportedLocales, true) ? $requested : $app->defaultLocale;
        Services::request()->setLocale($locale);

        return $this->response
            ->setStatusCode(404)
            ->setBody(view('Modules\Site\Views\errors\not_found', [
                'title'           => lang('Site.notfound.meta_title'),
                'metaDescription' => lang('Site.notfound.meta'),
                // Nothing here should be offered to a search engine.
                'noIndex'         => true,
            ]));
    }
}
