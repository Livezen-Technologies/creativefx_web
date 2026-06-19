<?php

namespace Modules\Core\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\App;

/**
 * LocaleFilter — runs on the localized site routes. It reads the leading URI
 * segment (constrained by the (:locale) placeholder to a supported locale),
 * applies it to the request, and persists the choice in a cookie.
 */
class LocaleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config    = config(App::class);
        $segments  = explode('/', trim($request->getUri()->getPath(), '/'));
        $candidate = $segments[0] ?? $config->defaultLocale;

        $locale = in_array($candidate, $config->supportedLocales, true)
            ? $candidate
            : $config->defaultLocale;

        $request->setLocale($locale);
        service('response')->setCookie('locale', $locale, YEAR);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
