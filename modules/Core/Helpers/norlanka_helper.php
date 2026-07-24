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

if (! function_exists('rich_text')) {
    /**
     * Render a detail field that may contain editor-authored HTML.
     * Locale maps resolve through t_field(). HTML content is sanitized
     * (scripts, event handlers, javascript: URIs stripped) and wrapped in an
     * .article-body block for typography; plain text keeps the classic
     * escaped nl2br rendering, so legacy content is untouched.
     */
    function rich_text($value, ?string $locale = null): string
    {
        $text = is_array($value) ? t_field($value, $locale) : (string) $value;
        if (trim($text) === '') {
            return '';
        }

        if (preg_match('/^\s*<(?:p|h[1-6]|ul|ol|blockquote|div|figure|strong|em|br)[\s>\/]/i', $text)) {
            $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $text);
            $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', (string) $html);
            $html = preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2/i', '$1="#"', (string) $html);

            return '<div class="article-body">' . $html . '</div>';
        }

        return nl2br(esc($text));
    }
}
