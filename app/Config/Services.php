<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use CodeIgniter\HTTP\IncomingRequest;
use Locale;
use Modules\Translation\Libraries\DbLanguage;

/**
 * Services Configuration file.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. For more examples, see the core Services file at
 * system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Override the Language service with a database-backed one so editor
     * translations (translations table) take precedence over file strings.
     */
    public static function language(?string $locale = null, bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('language', $locale)->setLocale($locale);
        }

        $request       = service('request');
        $requestLocale = $request instanceof IncomingRequest ? $request->getLocale() : Locale::getDefault();

        $locale = in_array($locale, [null, '', '0'], true) ? $requestLocale : $locale;

        return new DbLanguage($locale);
    }
}
