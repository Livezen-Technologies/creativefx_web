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

if (! function_exists('content_locales')) {
    /**
     * Complete a locale map from the content translation dictionary.
     *
     * Seeders author content in English (plus whatever locales they specify);
     * this fills any locale still missing from ContentTranslations.php, so
     * seeded content lands complete in en/ja/es/zh. Strings absent from the
     * dictionary are returned untouched and fall back via t_field().
     */
    function content_locales(array $map): array
    {
        static $dict = null;
        if ($dict === null) {
            $file = ROOTPATH . 'modules/Core/Config/ContentTranslations.php';
            $dict = is_file($file) ? (require $file) : [];
        }

        $en = trim((string) ($map['en'] ?? ''));
        if ($en === '' || ! isset($dict[$en])) {
            return $map;
        }

        foreach (['ja', 'es', 'zh'] as $locale) {
            if (trim((string) ($map[$locale] ?? '')) === '') {
                $map[$locale] = $dict[$en][$locale];
            }
        }

        return $map;
    }
}

if (! function_exists('is_cutout_image')) {
    /**
     * Is this image a cut-out — a subject on transparency — or a photograph?
     *
     * Cut-outs have to be shown whole; a cover crop lops the product off.
     * Photographs want the crop so they fill the frame. The file extension is
     * no guide here: the shop's pack shots are PNGs too, they just have an
     * opaque background.
     *
     * So read the PNG header, which is enough to answer it: colour type 4 and
     * 6 carry an alpha channel, and type 3 (palette) is transparent only when
     * a tRNS chunk follows. Both live in the first few dozen bytes, and the
     * answer is memoised, so this costs one short read per distinct image.
     */
    function is_cutout_image(?string $path): bool
    {
        static $seen = [];

        if ($path === null || preg_match('/\.png(\?.*)?$/i', $path) !== 1) {
            return false;
        }

        $file = FCPATH . ltrim(strtok($path, '?'), '/');
        if (isset($seen[$file])) {
            return $seen[$file];
        }

        $head = @file_get_contents($file, false, null, 0, 2048);
        if ($head === false || strncmp($head, "\x89PNG\r\n\x1a\n", 8) !== 0) {
            return $seen[$file] = false;
        }

        $colourType = ord($head[25]);

        return $seen[$file] = match ($colourType) {
            4, 6    => true,                            // grey/RGB with alpha
            3       => str_contains($head, 'tRNS'),     // palette + transparency
            default => false,
        };
    }
}
