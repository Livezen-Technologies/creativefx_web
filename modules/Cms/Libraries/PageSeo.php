<?php

namespace Modules\Cms\Libraries;

/**
 * The SEO metadata for one page, resolved once.
 *
 * Two controllers render CMS pages and both were assembling this by hand, which
 * is how the home page once ended up titling itself "Kukuleganga Giants Forest
 * — Kukuleganga Giants Forest". Every field has a fallback chain, and the
 * chains are the interesting part:
 *
 *   - An Open Graph title falls back to the meta title, then the page title.
 *     A share card is read on its own and a search result in a list of ten, so
 *     the two are worth writing separately — but an empty one must never win.
 *   - The canonical defaults to the page's own address. Setting it is for the
 *     case where two URLs genuinely serve the same page; leaving it blank is
 *     the normal answer, not a missing value.
 *   - Keywords are carried because the brief asks for them. They have not been
 *     a Google ranking signal for many years; they cost nothing here and are
 *     emitted only when an editor has actually written some.
 */
final class PageSeo
{
    public function __construct(private array $page = [])
    {
    }

    public static function for(?array $page): self
    {
        return new self($page ?? []);
    }

    /** Everything the layout needs, ready to pass straight into view(). */
    public function toViewData(?string $currentUrl = null): array
    {
        $title       = $this->text('meta_title') ?: $this->text('title');
        $description = $this->text('meta_description');

        return [
            'title'           => $title !== '' ? $title : (string) setting('site_name', ''),
            'metaDescription' => $description,
            'metaKeywords'    => $this->text('meta_keywords'),
            'canonical'       => trim((string) ($this->page['canonical_url'] ?? '')) ?: $currentUrl,
            'ogTitle'         => $this->text('og_title') ?: $title,
            'ogDescription'   => $this->text('og_description') ?: $description,
            'ogImage'         => $this->page['og_image'] ?? null,
        ];
    }

    /** A locale-map column as a plain string in the current language. */
    private function text(string $column): string
    {
        $value = $this->page[$column] ?? null;
        if ($value === null || $value === '') {
            return '';
        }

        // Columns are stored as JSON locale maps, but a hand-edited row can hold
        // a bare string. t_field() handles both once the JSON is decoded.
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value   = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return trim((string) (is_array($value) ? t_field($value) : $value));
    }
}
