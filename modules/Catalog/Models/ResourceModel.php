<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * Free downloads — cheat sheets, prompt libraries, checklists.
 *
 * Nothing here sells anything, which is the point. Somebody who is not ready to
 * spend LKR 60,000 on a two-day class will take a one-page shortcut sheet, and
 * the address that comes with it is the start of the relationship that
 * eventually does buy. So a resource is a *page* first and a file second: it
 * ranks, it is read, and the download is the call to action on it.
 *
 * Two columns carry the decisions:
 *
 *   `gated` — whether the file is handed over immediately or in exchange for an
 *   email. Both are legitimate and which applies to a given asset is a
 *   marketing judgement that changes, so it is a flag rather than two features.
 *
 *   `file_path` — nullable, and null is a supported published state rather than
 *   a broken row. A resource announced before the designer has finished it
 *   still ranks, still takes the address, and says plainly that it is being
 *   written. Pretending otherwise means a download button that hands somebody a
 *   404.
 *
 * `download_count` is only ever incremented by `recordDownload()`, and only
 * when a file was genuinely served. A count that included the page views, or
 * the leads captured against a file that does not exist yet, would be a number
 * the marketing team makes decisions on and cannot trust.
 */
class ResourceModel extends Model
{
    protected $table         = 'resources';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'slug', 'type', 'title', 'summary', 'body', 'file_path', 'hero_image',
        'gated', 'download_count', 'seo_title', 'seo_description', 'seo_keywords',
        'is_custom', 'sort_order', 'status',
    ];

    /** Published, in the order the listing presents them. */
    public function live(): self
    {
        $this->where('status', 'published')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC');

        return $this;
    }

    public function findLive(string $slug): ?array
    {
        return $this->live()->where('slug', $slug)->first();
    }

    /**
     * One more download.
     *
     * Written straight through the query builder rather than through
     * `update()`, for two reasons that both matter.
     *
     * It is `download_count + 1` computed by the database rather than a read,
     * an addition in PHP and a write back: two people pressing the button in
     * the same second against a read-modify-write lose one of the two, and a
     * counter that silently under-reports is worse than no counter.
     *
     * And it bypasses `$useTimestamps`, deliberately. A download is not an edit
     * of the page. Letting it move `updated_at` would push a new `lastmod` into
     * the sitemap every time somebody took a copy, which tells a crawler the
     * document changed when it did not.
     */
    public function recordDownload(int $id): void
    {
        $this->db->table($this->table)
            ->where('id', $id)
            ->set('download_count', 'download_count + 1', false)
            ->update();
    }

    /**
     * The file on disk, or null when there is nothing to serve.
     *
     * Two storage roots are accepted and the leading slash decides which,
     * rather than a setting nobody will remember to change. A path beginning
     * with `/` is a media-library path under the web root, which is what the
     * admin's file picker writes; anything else is taken as relative to
     * `WRITEPATH`, which is where an asset that must not be reachable without
     * passing through the controller belongs.
     *
     * The path is admin-editable text and is therefore treated as untrusted
     * input rather than as the shape the file picker happens to produce.
     * `../../.env` under either root resolves to a real, readable file, and
     * without the containment check below the download button would hand it to
     * anybody who pressed it. `realpath()` is what does the work: it collapses
     * the traversal and the symlink before the prefix is compared, so a string
     * test for `..` is not needed and would not have been sufficient anyway.
     */
    public static function fileFor(array $resource): ?string
    {
        $path = trim((string) ($resource['file_path'] ?? ''));
        if ($path === '') {
            return null;
        }

        $root     = $path[0] === '/' ? FCPATH : WRITEPATH;
        $rootReal = realpath($root);
        $fileReal = realpath($root . ltrim($path, '/'));

        if ($rootReal === false || $fileReal === false) {
            return null;
        }
        if (! str_starts_with($fileReal, rtrim($rootReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($fileReal) ? $fileReal : null;
    }
}
