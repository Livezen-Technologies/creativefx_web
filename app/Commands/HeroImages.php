<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Media\Models\MediaModel;

/**
 * Promote an already-uploaded image to the home page hero, from the CLI.
 *
 * The reason this exists: uploading is the obvious action and the folder box on
 * the upload form is easy to miss, so the first real photograph landed in
 * `uploads` and the hero carried on showing its empty state. Re-uploading it
 * into the right folder works, but it is the wrong answer to "I already put the
 * file on your server" — and moving files by hand risks leaving the database
 * row pointing at a path that no longer exists.
 *
 * So this marks the row instead. The file stays exactly where it is; the hero
 * reads the `hero` tag as well as the `hero` folder.
 *
 *   php spark hero:images                          list what the hero will show
 *   php spark hero:images add 12                    by media id
 *   php spark hero:images add /media/uploads/x.jpg  by path or URL
 *   php spark hero:images remove 12
 */
class HeroImages extends BaseCommand
{
    protected $group       = 'TSHDA';
    protected $name        = 'hero:images';
    protected $description = 'List, add or remove the images the home page hero shows.';
    protected $usage       = 'hero:images [list|add|remove] [id|path]';

    public function run(array $params)
    {
        $action = $params[0] ?? 'list';
        $model  = model(MediaModel::class);

        if ($action === 'list') {
            return $this->list($model);
        }

        if (! in_array($action, ['add', 'remove'], true)) {
            CLI::error('Unknown action: ' . $action . '. Use list, add or remove.');

            return 1;
        }

        $needle = trim((string) ($params[1] ?? ''));
        if ($needle === '') {
            CLI::error('Which image? Give a media id, or the path it was uploaded to.');

            return 1;
        }

        $row = $this->find($model, $needle);

        // A file on the server with no row to its name. That is not a typo to
        // be reported, it is a repair to be made: this site's uploads survived
        // on disk while the media_library rows did not, so the photograph was
        // sitting in public/media/uploads with the hero showing its empty state
        // and no way in through the admin, which can only list what the table
        // knows about.
        //
        // Only on `add`, and only for a path. `remove` has nothing to do for a
        // row that does not exist, and adopting a file in order to immediately
        // strip a tag off it would be a strange way to spend a write.
        if ($row === null && $action === 'add' && ! ctype_digit($needle)) {
            $row = $this->adopt($model, $needle);
        }

        if ($row === null) {
            CLI::error('No image in the media library matches: ' . $needle);
            CLI::write('Run `php spark hero:images list` to see what is there.', 'dark_gray');

            return 1;
        }

        $tags = $this->tags($row['tags'] ?? '');

        if ($action === 'add') {
            if (in_array('hero', $tags, true)) {
                CLI::write('Already a hero image: ' . $row['path'], 'yellow');

                return 0;
            }
            $tags[] = 'hero';
        } else {
            $tags = array_values(array_filter($tags, static fn (string $t): bool => $t !== 'hero'));
        }

        $model->update($row['id'], ['tags' => implode(',', $tags) ?: null]);

        CLI::write(($action === 'add' ? 'Added to' : 'Removed from') . ' the hero: ' . $row['path'], 'green');
        CLI::newLine();

        return $this->list($model);
    }

    /**
     * Everything the hero will actually render, in the order it will render it.
     *
     * Asked of MediaModel::heroImages() rather than re-queried here. This
     * command used to carry its own copy of that query while the home page read
     * no media at all, so it reported an image as promoted and the front page
     * never changed — the failure a command like this exists to prevent.
     */
    private function list(MediaModel $model): int
    {
        $rows = $model->heroImages(50);

        // heroImages() drops rows whose file has gone, because the page must
        // not render a broken image. Here they are worth naming: "I promoted it
        // and it is not listed" needs an answer better than silence.
        $missing = 0;
        foreach ($model->groupStart()->where('folder', 'hero')->orLike('tags', 'hero')->groupEnd()->findAll(50) as $row) {
            $promoted = ($row['folder'] ?? '') === 'hero'
                || in_array('hero', MediaModel::tagsOf($row['tags'] ?? null), true);

            if ($promoted && ! is_file(FCPATH . ltrim((string) $row['path'], '/'))) {
                $missing++;
            }
        }

        if ($rows === []) {
            CLI::write('No hero photograph, so the home page shows its words full width.', 'yellow');
            CLI::newLine();

            // The candidates, because "I uploaded it" and "the hero shows it"
            // are two different things and this command could previously only
            // report the second. Somebody who has just uploaded a photograph and
            // is looking at an unchanged front page needs to be told the file
            // arrived and what to type next — not an empty list, which reads as
            // the upload having failed.
            $recent = $model->like('mime_type', 'image/', 'after')
                ->orderBy('id', 'DESC')
                ->findAll(10);

            if ($recent === []) {
                CLI::write('There are no images in the media library at all.', 'dark_gray');
                CLI::write('Upload one in the admin, then run this again.', 'dark_gray');
            } else {
                CLI::write('The most recent images in the library, any of which can be promoted:', 'dark_gray');
                CLI::table(array_map(static fn (array $r): array => [
                    (string) $r['id'],
                    (string) $r['path'],
                    trim((string) ($r['folder'] ?? '')) ?: '—',
                    is_file(FCPATH . ltrim((string) $r['path'], '/')) ? 'on disk' : 'MISSING',
                ], $recent), ['id', 'path', 'folder', 'file']);

                CLI::write('Promote one with:  php spark hero:images add ' . $recent[0]['id'], 'dark_gray');
            }
        } else {
            CLI::table(array_map(static fn (array $r): array => [
                (string) $r['id'],
                (string) $r['path'],
                trim((string) ($r['alt'] ?? '')) ?: 'NO ALT TEXT',
                trim((string) ($r['tags'] ?? '')) ?: '—',
            ], $rows), ['id', 'path', 'alt', 'tags']);

            CLI::write('The hero shows the first of these: ' . $rows[0]['path'], 'green');

            if (trim((string) ($rows[0]['alt'] ?? '')) === '') {
                CLI::write('It has no alt text. Set one in the admin media library — this is the', 'yellow');
                CLI::write('largest image on the site and a screen reader will announce nothing.', 'yellow');
            }
        }

        if ($missing > 0) {
            CLI::newLine();
            CLI::write($missing . ' promoted row(s) point at a file that is not on disk, and are skipped.', 'red');
        }

        return 0;
    }

    /**
     * Find a media row by id, or by any recognisable form of its path — the
     * stored `path`, the site-absolute `url`, or just the filename, because the
     * three get copied out of different places and a command that only accepts
     * one of them is a command people give up on.
     */
    /**
     * Register a file that is on disk but not in the library, and return it.
     *
     * Returns null when the path names nothing, points outside public/, or is
     * not an image — the last of those decided by getimagesize() rather than by
     * the extension, because the file is about to be put on the front page and
     * "it ends in .jpg" is not the same claim as "it is a JPEG".
     *
     * The columns are filled the way the admin uploader fills them: `path`
     * relative with no leading slash, `url` the same with one. A row written
     * any other way renders as a broken image in the most prominent place on
     * the site, which is the failure this command already exists to avoid.
     */
    private function adopt(MediaModel $model, string $needle): ?array
    {
        $rel = ltrim(trim($needle), '/');
        $abs = realpath(FCPATH . $rel);

        // realpath() resolves `..`, so this rejects a path that climbs out of
        // the web root even though the string looked relative.
        $root = realpath(FCPATH);
        if ($abs === false || $root === false || ! str_starts_with($abs, $root . DIRECTORY_SEPARATOR) || ! is_file($abs)) {
            return null;
        }

        $size = @getimagesize($abs);
        if ($size === false) {
            CLI::error('Not an image: ' . $rel);

            return null;
        }

        $id = $model->insert([
            'disk'          => 'local',
            'path'          => $rel,
            'url'           => '/' . $rel,
            'original_name' => basename($rel),
            'mime_type'     => $size['mime'] ?? (mime_content_type($abs) ?: null),
            'size_bytes'    => filesize($abs) ?: null,
            'hash'          => @sha1_file($abs) ?: null,
            'width'         => (int) $size[0],
            'height'        => (int) $size[1],
        ], true);

        if (! $id) {
            CLI::error('Could not register ' . $rel . ': ' . implode('; ', $model->errors()));

            return null;
        }

        CLI::write('Registered a file that was on disk but not in the library: ' . $rel, 'green');

        return $model->find((int) $id);
    }

    private function find(MediaModel $model, string $needle): ?array
    {
        if (ctype_digit($needle)) {
            $row = $model->find((int) $needle);
            if ($row !== null) {
                return $row;
            }
        }

        $bare = ltrim($needle, '/');

        return $model->where('path', $bare)->first()
            ?? $model->where('url', '/' . $bare)->first()
            ?? $model->like('path', basename($bare))->first();
    }

    /** @return list<string> */
    private function tags(?string $raw): array
    {
        return MediaModel::tagsOf($raw);
    }
}
