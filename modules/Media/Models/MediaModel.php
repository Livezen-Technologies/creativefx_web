<?php

namespace Modules\Media\Models;

use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table         = 'media_library';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'disk', 'path', 'original_name', 'url', 'mime_type', 'size_bytes', 'hash', 'width', 'height', 'alt', 'folder', 'tags', 'uploaded_by',
    ];

    /**
     * The images the home page hero may show, in the order it shows them.
     *
     * An image qualifies by living in the `hero` folder *or* by carrying the
     * `hero` tag. Two ways in, because uploading is the obvious action and the
     * folder box on the upload form is easy to miss: the tag promotes a
     * photograph where it already sits, with no moving of files and no
     * uploading it twice.
     *
     * This is the only definition of "a hero image". It used to live inside
     * `spark hero:images` and nothing else read it, so the command could report
     * an image as promoted while the page showed something else — and on this
     * site the page showed nothing at all, which meant the command reported
     * success and changed nothing anybody could see.
     *
     * @return list<array>
     */
    public function heroImages(int $limit = 6): array
    {
        $rows = $this
            ->groupStart()
                ->where('folder', 'hero')
                ->orLike('tags', 'hero')
            ->groupEnd()
            ->like('mime_type', 'image/', 'after')
            ->orderBy('original_name', 'ASC')
            ->findAll(50);

        $out = [];
        foreach ($rows as $row) {
            // `orLike('tags', 'hero')` also matches "heroine" and "hero-shot",
            // and neither is a request to put a photograph on the front page.
            // The whole-word test is made here rather than in SQL because
            // deciding whether `hero` is one of a comma-separated list means
            // string concatenation, and that is `||` in SQLite and CONCAT() in
            // MySQL — this site runs on both.
            if (($row['folder'] ?? '') !== 'hero' && ! in_array('hero', self::tagsOf($row['tags'] ?? null), true)) {
                continue;
            }

            // A row pointing at a file that has gone would render as a broken
            // image in the most prominent place on the site.
            if (! is_file(FCPATH . ltrim((string) $row['path'], '/'))) {
                continue;
            }

            $out[] = $row;

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }

    /**
     * The site-absolute URL of a media row.
     *
     * `media_library.path` is stored without a leading slash — `media/hero/x.jpg`
     * — and used raw it is a *relative* URL: correct on `/`, and a 404 on `/en`
     * and every other page, because the browser resolves it against the current
     * directory. media_src() deliberately passes non-rooted values through
     * untouched (that is how absolute URLs and data: URIs get past it), so it
     * does not rescue this and does not add its cache-buster either.
     *
     * Rooting it here rather than at each call site, because the next person to
     * render a media row will reach for `path` exactly as I did.
     */
    public static function urlOf(array $row): string
    {
        $url = trim((string) ($row['url'] ?? ''));
        if ($url === '') {
            $url = '/' . ltrim((string) ($row['path'] ?? ''), '/');
        }

        return $url === '/' ? '' : $url;
    }

    /** @return list<string> */
    public static function tagsOf(?string $raw): array
    {
        return array_values(array_filter(array_map(
            static fn (string $t): string => strtolower(trim($t)),
            explode(',', (string) $raw)
        ), static fn (string $t): bool => $t !== ''));
    }
}
