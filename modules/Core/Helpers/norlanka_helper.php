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
            'si' => ['Sinhala', 'සිංහල'],
            'ta' => ['Tamil', 'தமிழ்'],
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
        // Always through t_field(), not only when the value is already an array.
        //
        // A translatable column arrives from the database as the JSON *string*
        // `{"en":"<p>…"}`, not as a decoded array. Treating a string as
        // finished text meant that JSON fell straight past the markup test
        // below and came out through esc() — so a course description rendered
        // as a visible wall of `{"en":"<p>Most people meet Photoshop…` on the
        // page. t_field() resolves both shapes, and passes plain prose through
        // untouched, so there is no case where this is the wrong thing to do.
        $text = t_field($value, $locale);
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

        foreach (translatable_locales() as $locale) {
            if (isset($dict[$en][$locale]) && trim((string) ($map[$locale] ?? '')) === '') {
                $map[$locale] = $dict[$en][$locale];
            }
        }

        return $map;
    }
}

if (! function_exists('translatable_locales')) {
    /**
     * The locales content has to be translated INTO — every supported locale
     * except the one it is authored in.
     *
     * The locale list used to be repeated as a literal in six files, which is
     * how three of them were still listing Japanese, Spanish and Chinese after
     * the site became Sri Lankan. Everything derives it from App::$supportedLocales
     * now, so adding or dropping a language is one edit.
     *
     * @return list<string>
     */
    function translatable_locales(): array
    {
        $all     = config('App')->supportedLocales;
        $default = config('App')->defaultLocale;

        return array_values(array_filter($all, static fn ($l) => $l !== $default));
    }
}

if (! function_exists('supported_locales_codes')) {
    /**
     * Just the codes, in configured order.
     *
     * supported_locales() returns rich rows for the switcher; a template that
     * only needs to iterate the languages was writing ['en','si','ta'] by hand,
     * which is a second list to forget when a language is added or dropped —
     * and it was forgotten, leaving a dialog offering a language the router
     * would 404.
     *
     * @return list<string>
     */
    function supported_locales_codes(): array
    {
        return config('App')->supportedLocales;
    }
}

if (! function_exists('media_src')) {
    /**
     * Stamp a local media path with the file's own modification time.
     *
     * nginx serves /media/ with `expires 30d`, which is right for photography
     * that rarely changes and wrong the moment one does: the URL never moves,
     * so a browser that saw the old file keeps showing it for a month. Relighting
     * the home hero is exactly that case — the origin was serving the new
     * photograph while visitors carried on seeing the old one.
     *
     * The key is the file's size rather than its mtime: the deploy's
     * `git reset --hard` restamps mtimes on every run, so an mtime key would
     * re-bust every photograph on the site each time anything shipped. Size is
     * a stat rather than a read, costs nothing, survives a checkout, and moves
     * whenever an image is genuinely re-encoded — the relight took this one
     * from 392243 bytes to 410989.
     *
     * It rides in a query string, which nginx ignores when resolving a static
     * file, so nothing about how the file is served changes; only the cache key
     * does. Absolute URLs, data URIs and paths with no file behind them are
     * handed back untouched.
     */
    function media_src(?string $path): string
    {
        static $seen = [];

        $path = (string) $path;
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//')) {
            return $path;
        }

        if (isset($seen[$path])) {
            return $seen[$path];
        }

        [$file, $query] = array_pad(explode('?', $path, 2), 2, null);
        $size = @filesize(FCPATH . ltrim($file, '/'));

        if ($size === false) {
            return $seen[$path] = $path;
        }

        return $seen[$path] = $file . ($query === null ? '?' : '?' . $query . '&') . 'v=' . $size;
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

if (! defined('DEFAULT_NAV')) {
    /**
     * The menu the site falls back to when the database cannot answer.
     *
     * Not a second copy of the seeded rows so much as the floor beneath them: a
     * failed query, a request before migrations have run, or a database that is
     * briefly unreachable should cost a visitor a stale menu, not a header with
     * nothing in it.
     *
     * Every entry has to be a route that actually exists and a language key
     * that actually resolves. It has been wrong on both counts before: after a
     * rebrand it still listed the previous site's sections, so the fallback
     * rendered five links reading "Site.nav.about" and pointing at 404s — on
     * precisely the occasions when something was already going wrong.
     */
    define('DEFAULT_NAV', [
        ['url' => 'courses',   'label' => 'Site.nav.courses'],
        ['url' => 'schedule',  'label' => 'Site.nav.schedule'],
        ['url' => 'corporate', 'label' => 'Site.home.corporate_heading'],
        ['url' => 'blog',      'label' => 'Site.news.title'],
        ['url' => 'contact',   'label' => 'Site.nav.contact'],
    ]);
}

if (! function_exists('menu_link')) {
    /**
     * Turn a stored menu URL into one a browser can follow.
     *
     * An editor types "contact", not "/en/contact", and should not have to know
     * the site is localized. Anything that already names a scheme, or starts at
     * the site root, or is an in-page anchor, is left exactly as written — that
     * is how a booking engine on another domain, or a link straight to a PDF,
     * gets through unmangled.
     */
    function menu_link(?string $url): string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return locale_url('');
        }
        if (preg_match('~^([a-z][a-z0-9+.-]*:|//|/|#)~i', $url) === 1) {
            return $url;
        }

        return locale_url($url);
    }
}

if (! function_exists('menu_label')) {
    /**
     * A menu item's text.
     *
     * Seeded items store a language key rather than a word, so an item nobody
     * has renamed still follows the translation files and still switches with
     * the locale. Once an editor types their own label it is a plain string and
     * is shown as typed — lang() returns its argument unchanged when it cannot
     * resolve it, so both cases run through the same call safely.
     */
    function menu_label(array $item): string
    {
        $label = $item['label'] ?? '';

        if (is_string($label)) {
            $decoded = json_decode($label, true);
            $label   = json_last_error() === JSON_ERROR_NONE ? $decoded : $label;
        }

        $text = is_array($label) ? t_field($label) : (string) $label;
        $text = trim($text);

        return $text === '' ? '' : lang($text);
    }
}

if (! function_exists('site_nav')) {
    /**
     * One navigation menu, ready to render.
     *
     * Reads the menu_items table so labels, order, nesting and visibility are
     * editable in the console. Falls back to DEFAULT_NAV if the table is empty
     * or unreachable, and caches per location because the header and the footer
     * both ask for one on every request.
     *
     * @return list<array{label:string,url:string,slug:string,target:string,children:list<array>}>
     */
    function site_nav(string $location = 'header'): array
    {
        static $cache = [];

        if (isset($cache[$location])) {
            return $cache[$location];
        }

        $items = [];

        try {
            $items = model('Modules\Cms\Models\MenuItemModel')->tree($location);
        } catch (\Throwable $e) {
            $items = [];
        }

        if ($items === []) {
            $items = array_map(static fn (array $i): array => $i + ['children' => []], DEFAULT_NAV);
        }

        $shape = static function (array $item) use (&$shape): array {
            return [
                'label'    => menu_label($item),
                'url'      => menu_link($item['url'] ?? ''),
                // The raw slug, for the active-state comparison the header does.
                'slug'     => trim((string) ($item['url'] ?? '')),
                'target'   => ($item['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
                'children' => array_map($shape, $item['children'] ?? []),
            ];
        };

        return $cache[$location] = array_values(array_filter(
            array_map($shape, $items),
            static fn (array $i): bool => $i['label'] !== '',
        ));
    }
}

if (! function_exists('source_link')) {
    /**
     * A citation under a paragraph: "Sri Lanka Tourism →".
     *
     * Rendered here rather than in each block so every one of them carries
     * rel="noopener noreferrer" and target="_blank" without that having to be
     * remembered four times. An off-site link opened with target="_blank" and
     * no rel hands the new page a reference back to this one.
     *
     * @param array{label?: array|string, url?: string} $link
     */
    function source_link(array $link, string $class = ''): string
    {
        $url   = trim((string) ($link['url'] ?? ''));
        $label = $link['label'] ?? '';
        $label = is_array($label) ? t_field($label) : (string) $label;

        if ($url === '' || trim($label) === '') {
            return '';
        }

        $external = str_starts_with($url, 'http');

        return sprintf(
            '<a href="%s"%s class="%s">%s<span aria-hidden="true"> &rarr;</span></a>',
            esc($url, 'attr'),
            $external ? ' target="_blank" rel="noopener noreferrer"' : '',
            esc(trim('nl-source ' . $class), 'attr'),
            esc($label),
        );
    }
}

if (! function_exists('booking_open')) {
    /**
     * Attributes that make a link open the booking dialog instead of navigating.
     *
     * The same three-line ternary had been copied into every template that
     * renders a call to action, which is how two of them ended up without it:
     * the room cards each carry a Book Now that walked the visitor to the
     * contact page while every other Book Now on the site opened the form in
     * place. One function, called from every button, cannot drift like that.
     *
     * The href stays on the anchor and the default is only prevented once
     * Alpine is running, so the contact page is still where it goes without
     * JavaScript. x-data is bare and local because $dispatch walks up for an
     * Alpine scope and a plain page gives it none.
     */
    function booking_open(array $content): string
    {
        return ($content['modal'] ?? '') === 'booking'
            ? 'x-data @click.prevent="$dispatch(\'booking-open\')"'
            : '';
    }
}

if (! function_exists('stars')) {
    /**
     * A five-star rating, as filled stars followed by unfilled ones.
     *
     * The clamp is the whole point. `str_repeat('★', 5 - $rating)` throws a
     * ValueError in PHP 8 the moment `$rating` exceeds five, and that is not a
     * theoretical input: `reviews.rating` is a TINYINT, the migration that
     * created it explicitly anticipates reviews imported from elsewhere, and
     * half the review platforms in the world are marked out of ten. One
     * imported row would have taken down the course page, the reviews index and
     * the home page — every page that shows a review — with a 500.
     *
     * It was written out seven times across four modules. Once here means the
     * eighth cannot get it wrong.
     *
     * Returns markup, so callers echo it unescaped; everything in it is static
     * text and a class name the caller chose.
     */
    function stars(int $rating, string $dimClass = 'text-white/20'): string
    {
        $filled = max(0, min(5, $rating));

        return str_repeat('★', $filled)
            . ($filled < 5
                ? '<span class="' . esc($dimClass, 'attr') . '">' . str_repeat('★', 5 - $filled) . '</span>'
                : '');
    }
}

if (! function_exists('publishable')) {
    /**
     * Text fit to print, or null.
     *
     * Editorial fields arrive from a seed file or from somebody typing into the
     * admin, and both routes have produced copy with the gap still in it —
     * "{to be confirmed}" where a street address belongs. A page that prints
     * that has not merely failed to say something, it has said something
     * obviously broken, which costs more trust than the blank would have.
     *
     * So a value carrying a curly-braced token is treated as absent, and the
     * caller falls through to whatever it says when there is nothing: usually a
     * sentence explaining when the detail will be known, which is a real answer.
     *
     * `spark check:placeholders` catches these before a deploy. This is the
     * second line: the check reads what is in the repository and the database
     * today, and cannot see what an editor types tomorrow.
     */
    function publishable(?string $text): ?string
    {
        $text = trim((string) $text);

        if ($text === '' || preg_match('/\{[^}]*\}/', $text) === 1) {
            return null;
        }

        return $text;
    }
}

if (! function_exists('blog_url')) {
    /**
     * The blog listing, optionally filtered.
     *
     * The section moved from /news to /blog in the rebrand and three
     * hand-built URLs did not move with it — the "All" chip, every category
     * chip and the whole pager, on the index and on each article. They all
     * pointed at a route that does not exist, so the articles were reachable
     * and nothing else on the page was.
     *
     * @param array<string,string|int|null> $params category, page
     */
    function blog_url(array $params = []): string
    {
        $query = http_build_query(array_filter(
            $params,
            static fn ($v): bool => $v !== null && $v !== '' && $v !== 1,
        ));

        return locale_url('blog') . ($query === '' ? '' : '?' . $query);
    }
}

if (! function_exists('post_url')) {
    /**
     * The address of a blog post.
     *
     * The section is served at /blog. It used to be /news, and the rebrand
     * changed the routes without changing the two views that build the links —
     * so the blog index listed six articles and every one of them 404'd, while
     * the sitemap advertised the correct /blog addresses to crawlers. The
     * section was reachable and unreadable at the same time, which is why
     * nobody clicking around the navigation would have found it.
     *
     * Four places were assembling this string. One is enough, and it is the
     * same shape as `course_url()` and `session_url()` beside it.
     */
    function post_url(string $slug): string
    {
        return locale_url('blog/' . $slug);
    }
}

if (! function_exists('safe_timezone')) {
    /**
     * A timezone that exists, whatever the database says.
     *
     * `course_sessions.timezone` and `venues.timezone` are free-text admin
     * fields validated only for length, and `new DateTimeZone('Asia/Colobmo')`
     * throws. Nine places built one straight from a column and one of them
     * wrapped it — so a single typo in the admin took down the learner's
     * joining page, the session page, the city page and the JSON-LD on all of
     * them, with a 500 rather than a wrong time.
     *
     * Falling back is the right failure here: a class shown in the school's own
     * zone is slightly wrong for a learner abroad, and a page that will not
     * render is wrong for everybody. The fallback is logged so the typo is
     * findable rather than merely survivable.
     */
    function safe_timezone(?string $id, string $fallback = 'Asia/Colombo'): DateTimeZone
    {
        $id = trim((string) $id);

        if ($id !== '') {
            try {
                return new DateTimeZone($id);
            } catch (Throwable) {
                log_message('warning', 'Invalid timezone "{tz}" in the database; falling back to {fallback}.', [
                    'tz'       => $id,
                    'fallback' => $fallback,
                ]);
            }
        }

        try {
            return new DateTimeZone($fallback);
        } catch (Throwable) {
            // The fallback is a literal in this file, so this is unreachable
            // unless the timezone database itself is missing — in which case
            // UTC is the only thing left that is guaranteed to exist.
            return new DateTimeZone('UTC');
        }
    }
}
