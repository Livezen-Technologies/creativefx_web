<?php

/**
 * Norlanka shared helpers — localisation + settings access used across modules.
 */

if (! function_exists('current_locale')) {
    function current_locale(): string
    {
        $locale = service('request')->getLocale();
        return $locale ?: config('App')->defaultLocale;
    }
}

if (! function_exists('supported_locales')) {
    /**
     * @return list<array{code:string,label:string,native:string}>
     */
    function supported_locales(): array
    {
        $labels = [
            'en' => ['English', 'English'],
            'ja' => ['Japanese', '日本語'],
            'es' => ['Spanish', 'Español'],
            'zh' => ['Chinese', '中文'],
        ];

        $out = [];
        foreach (config('App')->supportedLocales as $code) {
            $out[] = [
                'code'   => $code,
                'label'  => $labels[$code][0] ?? strtoupper($code),
                'native' => $labels[$code][1] ?? strtoupper($code),
            ];
        }
        return $out;
    }
}

if (! function_exists('t_field')) {
    /**
     * Resolve a translatable field. Accepts a JSON locale-map string, an array
     * locale-map, or a plain string. Falls back: requested locale -> en -> first.
     *
     * @param array<string,string>|string|null $value
     */
    function t_field($value, ?string $locale = null, string $fallback = 'en'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                return $value; // plain (non-JSON) string
            }
        }

        if (! is_array($value)) {
            return (string) $value;
        }

        $locale ??= current_locale();

        if (! empty($value[$locale])) {
            return (string) $value[$locale];
        }
        if (! empty($value[$fallback])) {
            return (string) $value[$fallback];
        }
        foreach ($value as $v) {
            if ($v !== null && $v !== '') {
                return (string) $v;
            }
        }
        return '';
    }
}

if (! function_exists('locale_url')) {
    /**
     * Build a locale-prefixed site URL: locale_url('our-story') -> /en/our-story
     */
    function locale_url(string $path = '', ?string $locale = null): string
    {
        helper('url');
        $locale ??= current_locale();
        $path = ltrim($path, '/');
        return site_url($path === '' ? $locale : $locale . '/' . $path);
    }
}

if (! function_exists('setting')) {
    /**
     * Read a value from the `settings` table (cached per request).
     */
    function setting(string $key, $default = null, string $group = 'general')
    {
        static $cache = null;

        if ($cache === null) {
            $cache = [];
            try {
                $rows = model('Modules\Core\Models\SettingModel')->findAll();
                foreach ($rows as $row) {
                    $cache[$row['group'] . '.' . $row['key']] = $row['value'];
                }
            } catch (\Throwable $e) {
                $cache = [];
            }
        }

        return $cache[$group . '.' . $key] ?? $default;
    }
}
