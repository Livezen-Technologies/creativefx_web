<?php

namespace Modules\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\App;

/**
 * LocaleFilter — validates the {locale} URL segment, applies it to the request,
 * and persists the choice in a cookie. Invalid/missing locales fall back to the
 * app default.
 */
class LocaleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config    = config(App::class);
        $supported = $config->supportedLocales;

        // The route passes the matched {locale} segment as the first argument.
        $locale = is_array($arguments) && isset($arguments[0]) ? $arguments[0] : null;

        if ($locale === null) {
            // Fall back to a previously chosen locale cookie, else the default.
            $locale = $request->getCookie('locale') ?: $config->defaultLocale;
        }

        if (! in_array($locale, $supported, true)) {
            $locale = $config->defaultLocale;
        }

        $request->setLocale($locale);
        service('response')->setCookie('locale', $locale, YEAR);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
