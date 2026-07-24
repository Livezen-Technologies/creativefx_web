<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Centralized Media Manager.
 *
 * Backend for both the standalone Media Library page and the media-picker
 * modal embedded across the admin (rich-text editor, image/video fields).
 *
 * Capabilities: bulk & AJAX uploads with SHA-1 duplicate detection, automatic
 * WebP optimization + 480px thumbnails for images, folders, tags, search and
 * type filters, rename / move / replace / metadata editing, and role-based
 * permissions (media.view / media.upload / media.edit / media.delete).
 */
class Media extends BaseController
{
    private const TYPE_FILTERS = ['image', 'video', 'document', '3d'];

    public function __construct()
    {
        helper(['admin', 'url']);
    }

    /** Standalone Media Library page (the browser UI is client-rendered). */
    public function index()
    {
        if (! admin_can('media.view')) {
            return redirect()->to(site_url('admin'))->with('error', 'You do not have permission to view media.');
        }

        return view('Modules\Admin\Views\media', [
            'title'  => 'Media Library',
            'active' => 'media',
            'types'  => self::TYPE_FILTERS,
            'caps'   => $this->caps(),
        ]);
    }

    /**
     * JSON listing for the browser/picker: filters (q, type, folder), paging,
     * plus the folder list and the caller's capabilities.
     */
    public function list()
    {
        if (! admin_can('media.view')) {
            return $this->deny();
        }

        $model  = model('Modules\Media\Models\MediaModel');
        $q      = trim((string) $this->request->getGet('q'));
        $type   = (string) $this->request->getGet('type');
        $folder = trim((string) $this->request->getGet('folder'));
        $page   = max(1, (int) $this->request->getGet('page'));
        $per    = 60;

        $builder = $model->orderBy('id', 'DESC');
        if ($q !== '') {
            $builder->groupStart()
                ->like('path', $q)->orLike('original_name', $q)->orLike('tags', $q)
                ->orLike('folder', $q)->orLike('alt', $q)
                ->groupEnd();
        }
        if ($folder !== '') {
            $builder->where('folder', $folder);
        }
        if ($type === 'image' || $type === 'video') {
            $builder->like('mime_type', $type . '/', 'after');
        } elseif ($type === 'document') {
            $builder->groupStart()
                ->like('path', '.pdf', 'before')->orLike('path', '.doc', 'before')->orLike('path', '.docx', 'before')
                ->orLike('path', '.xls', 'before')->orLike('path', '.xlsx', 'before')->orLike('path', '.zip', 'before')
                ->groupEnd();
        } elseif ($type === '3d') {
            $builder->groupStart()->like('path', '.glb', 'before')->orLike('path', '.gltf', 'before')->groupEnd();
        }

        $total = (clone $builder)->countAllResults(false);
        $rows  = $builder->findAll($per, ($page - 1) * $per);

        $db      = db_connect();
        $folders = array_column(
            $db->table('media_library')->select('folder')->distinct()->where('folder IS NOT NULL')->orderBy('folder')->get()->getResultArray(),
            'folder'
        );

        return $this->response->setJSON([
            'items'   => array_map([$this, 'present'], $rows),
            'total'   => $total,
            'page'    => $page,
            'pages'   => (int) ceil($total / $per),
            'folders' => array_values(array_filter($folders)),
            'caps'    => $this->caps(),
            'csrf'    => csrf_hash(),
        ]);
    }

    /** Classic multi-file form upload (Media page fallback). */
    public function upload()
    {
        if (! admin_can('media.upload')) {
            return redirect()->to(site_url('admin/media'))->with('error', 'You do not have permission to upload media.');
        }

        $files = $this->request->getFileMultiple('files') ?: [];
        $files = array_filter($files, static fn ($f) => $f !== null && $f->isValid() && ! $f->hasMoved());
        if ($files === []) {
            return redirect()->to(site_url('admin/media'))->with('error', 'No valid files uploaded.');
        }

        $folder = $this->cleanFolder((string) $this->request->getPost('folder'));
        $tags   = trim((string) $this->request->getPost('tags'));

        $count = 0;
        $dupes = 0;
        foreach ($files as $file) {
            $row = $this->storeFile($file, $folder, $tags);
            $row['duplicate'] ? $dupes++ : $count++;
        }

        $msg = $count . ' file(s) uploaded to media/' . $folder . '.';
        if ($dupes > 0) {
            $msg .= ' ' . $dupes . ' duplicate(s) skipped (already in the library).';
        }

        return redirect()->to(site_url('admin/media'))->with('message', $msg);
    }

    /** JSON upload for the picker, editor and drag-and-drop fields. */
    public function uploadAjax()
    {
        if (! admin_can('media.upload')) {
            return $this->deny();
        }

        $file = $this->request->getFile('file');
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'No valid file received.']);
        }

        $folder = $this->cleanFolder((string) $this->request->getPost('folder'));
        $row    = $this->storeFile($file, $folder, trim((string) $this->request->getPost('tags')));
        $row['csrf'] = csrf_hash();

        return $this->response->setJSON($row);
    }

    /** Rename the file on disk (slug + original extension); URL changes. */
    public function rename($id)
    {
        if (! admin_can('media.edit')) {
            return $this->deny();
        }

        $model = model('Modules\Media\Models\MediaModel');
        $row   = $model->find($id);
        $name  = strtolower(trim((string) $this->request->getPost('name')));
        $name  = preg_replace('/[^a-z0-9_-]+/', '-', $name);
        $name  = trim((string) $name, '-');

        if ($row === null || $name === '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid file or name.', 'csrf' => csrf_hash()]);
        }

        $ext    = pathinfo($row['path'], PATHINFO_EXTENSION);
        $dir    = dirname(FCPATH . $row['path']);
        $target = $name . ($ext !== '' ? '.' . $ext : '');
        if (is_file($dir . '/' . $target) && $dir . '/' . $target !== FCPATH . $row['path']) {
            $target = $name . '-' . substr(uniqid(), -4) . ($ext !== '' ? '.' . $ext : '');
        }

        $this->moveWithSiblings(FCPATH . $row['path'], $dir . '/' . $target);

        $newPath = trim(str_replace(FCPATH, '', $dir), '/') . '/' . $target;
        $model->update($id, ['path' => $newPath, 'url' => '/' . $newPath]);

        return $this->response->setJSON(['ok' => true, 'item' => $this->present($model->find($id)), 'csrf' => csrf_hash()]);
    }

    /** Move the file to another folder under public/media; URL changes. */
    public function move($id)
    {
        if (! admin_can('media.edit')) {
            return $this->deny();
        }

        $model  = model('Modules\Media\Models\MediaModel');
        $row    = $model->find($id);
        $folder = $this->cleanFolder((string) $this->request->getPost('folder'));
        if ($row === null || $folder === '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid file or folder.', 'csrf' => csrf_hash()]);
        }

        $dir = FCPATH . 'media/' . $folder;
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $base = basename($row['path']);
        $this->moveWithSiblings(FCPATH . $row['path'], $dir . '/' . $base);

        $newPath = 'media/' . $folder . '/' . $base;
        $model->update($id, ['path' => $newPath, 'url' => '/' . $newPath, 'folder' => $folder]);

        return $this->response->setJSON(['ok' => true, 'item' => $this->present($model->find($id)), 'csrf' => csrf_hash()]);
    }

    /** Replace the file's content in place — the URL (and references) survive. */
    public function replace($id)
    {
        if (! admin_can('media.edit')) {
            return $this->deny();
        }

        $model = model('Modules\Media\Models\MediaModel');
        $row   = $model->find($id);
        $file  = $this->request->getFile('file');
        if ($row === null || $file === null || ! $file->isValid()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid file.', 'csrf' => csrf_hash()]);
        }

        $oldExt = strtolower(pathinfo($row['path'], PATHINFO_EXTENSION));
        $newExt = strtolower($file->getExtension() ?: pathinfo($file->getClientName(), PATHINFO_EXTENSION));
        if ($oldExt !== $newExt) {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => "Replacement must keep the same file type (.{$oldExt}).",
                'csrf'  => csrf_hash(),
            ]);
        }

        $abs = FCPATH . $row['path'];
        $file->move(dirname($abs), basename($abs), true);

        $mime = mime_content_type($abs) ?: $row['mime_type'];
        [$width, $height] = $this->imageMeta($abs, $mime);
        $this->makeWebpCopy($abs, $mime);
        $this->makeThumb($abs, $mime);

        $model->update($id, [
            'mime_type'  => $mime,
            'size_bytes' => filesize($abs) ?: null,
            'width'      => $width,
            'height'     => $height,
            'hash'       => @sha1_file($abs) ?: null,
        ]);

        return $this->response->setJSON(['ok' => true, 'item' => $this->present($model->find($id)), 'csrf' => csrf_hash()]);
    }

    /** Update alt text / tags / folder-independent metadata. */
    public function updateMeta($id)
    {
        if (! admin_can('media.edit')) {
            return $this->deny();
        }

        $model = model('Modules\Media\Models\MediaModel');
        if ($model->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found.', 'csrf' => csrf_hash()]);
        }

        $data = [];
        foreach (['alt', 'tags'] as $f) {
            $v = $this->request->getPost($f);
            if ($v !== null) {
                $data[$f] = trim((string) $v) ?: null;
            }
        }
        if ($data !== []) {
            $model->update($id, $data);
        }

        return $this->response->setJSON(['ok' => true, 'item' => $this->present($model->find($id)), 'csrf' => csrf_hash()]);
    }

    public function delete($id)
    {
        if (! admin_can('media.delete')) {
            return $this->request->isAJAX()
                ? $this->deny()
                : redirect()->to(site_url('admin/media'))->with('error', 'You do not have permission to delete media.');
        }

        $model = model('Modules\Media\Models\MediaModel');
        $row   = $model->find($id);
        if ($row !== null) {
            $abs = FCPATH . ($row['path'] ?? '');
            foreach ([$abs, $this->sibling($abs, '.webp'), $this->sibling($abs, '.thumb.webp')] as $f) {
                if ($f !== null && is_file($f)) {
                    @unlink($f);
                }
            }
            $model->delete($id);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()]);
        }
        return redirect()->to(site_url('admin/media'))->with('message', 'File deleted.');
    }

    // ---------------------------------------------------------------- helpers

    private function caps(): array
    {
        return [
            'view'   => admin_can('media.view'),
            'upload' => admin_can('media.upload'),
            'edit'   => admin_can('media.edit'),
            'delete' => admin_can('media.delete'),
        ];
    }

    private function deny()
    {
        return $this->response->setStatusCode(403)->setJSON(['error' => 'Permission denied.', 'csrf' => csrf_hash()]);
    }

    private function cleanFolder(string $folder): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9_-]/i', '', $folder)) ?: 'uploads';
    }

    /** API/JSON projection of a media row, including thumb/webp URLs. */
    private function present(array $row): array
    {
        $abs   = FCPATH . $row['path'];
        $thumb = $this->sibling($abs, '.thumb.webp');
        $webp  = $this->sibling($abs, '.webp');

        return [
            'id'       => (int) $row['id'],
            'url'      => $row['url'],
            'webp'     => $webp !== null && is_file($webp) ? '/' . str_replace(FCPATH, '', $webp) : null,
            'thumb'    => $thumb !== null && is_file($thumb) ? '/' . str_replace(FCPATH, '', $thumb) : null,
            'name'     => basename($row['path']),
            'original' => $row['original_name'] ?? null,
            'mime'     => $row['mime_type'],
            'size'     => (int) ($row['size_bytes'] ?? 0),
            'width'    => $row['width'] !== null ? (int) $row['width'] : null,
            'height'   => $row['height'] !== null ? (int) $row['height'] : null,
            'folder'   => $row['folder'],
            'tags'     => $row['tags'],
            'alt'      => $row['alt'],
            'date'     => $row['created_at'],
        ];
    }

    /** Sibling path with the extension swapped (….jpg → ….webp / ….thumb.webp). */
    private function sibling(string $abs, string $suffix): ?string
    {
        $out = preg_replace('/\.[a-z0-9]+$/i', $suffix, $abs);
        return $out !== $abs ? $out : null;
    }

    /** Move a file together with its generated .webp / .thumb.webp siblings. */
    private function moveWithSiblings(string $from, string $to): void
    {
        if (is_file($from)) {
            rename($from, $to);
        }
        foreach (['.webp', '.thumb.webp'] as $suffix) {
            $fs = $this->sibling($from, $suffix);
            $ts = $this->sibling($to, $suffix);
            if ($fs !== null && $ts !== null && is_file($fs)) {
                rename($fs, $ts);
            }
        }
    }

    /**
     * Move an uploaded file into public/media/{folder} and register it.
     * Duplicate uploads (same SHA-1) return the existing record untouched.
     */
    private function storeFile($file, string $folder, string $tags = ''): array
    {
        $model = model('Modules\Media\Models\MediaModel');

        // Duplicate detection before anything is stored.
        $hash = @sha1_file($file->getTempName()) ?: null;
        if ($hash !== null) {
            $existing = $model->where('hash', $hash)->first();
            if ($existing !== null) {
                return $this->present($existing) + ['duplicate' => true];
            }
        }

        $dir = FCPATH . 'media/' . $folder;
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $original = $file->getClientName();
        $name     = $file->getRandomName();
        $size     = $file->getSize();
        $mime     = $file->getMimeType();
        $file->move($dir, $name);
        $abs = $dir . '/' . $name;

        [$width, $height] = $this->imageMeta($abs, $mime);
        $this->makeWebpCopy($abs, $mime);
        $this->makeThumb($abs, $mime);

        $id = $model->insert([
            'disk'          => 'local',
            'path'          => 'media/' . $folder . '/' . $name,
            'original_name' => $original,
            'url'           => '/media/' . $folder . '/' . $name,
            'mime_type'     => $mime,
            'size_bytes'    => $size,
            'hash'          => $hash,
            'width'         => $width,
            'height'        => $height,
            'folder'        => $folder,
            'tags'          => $tags !== '' ? $tags : null,
            'uploaded_by'   => session()->get('admin_user')['id'] ?? null,
        ]);

        $row = $this->present($model->find($id));
        // Prefer the optimized WebP for embedding when it exists.
        if (! empty($row['webp'])) {
            $row['url'] = $row['webp'];
        }
        $row['duplicate'] = false;

        return $row;
    }

    /** @return array{0:?int,1:?int} */
    private function imageMeta(string $abs, ?string $mime): array
    {
        if (! str_starts_with((string) $mime, 'image/')) {
            return [null, null];
        }
        $info = @getimagesize($abs);
        return $info ? [(int) $info[0], (int) $info[1]] : [null, null];
    }

    /**
     * Optimized WebP copy alongside the original (max 1920px wide, q80).
     * Silently skipped when GD/WebP is unavailable or the source isn't a
     * bitmap we can decode.
     */
    private function makeWebpCopy(string $abs, ?string $mime): void
    {
        $this->encodeWebp($abs, $mime, 1920, 80, '.webp');
    }

    /** 480px grid thumbnail (….thumb.webp). */
    private function makeThumb(string $abs, ?string $mime): void
    {
        $this->encodeWebp($abs, $mime, 480, 74, '.thumb.webp');
    }

    private function encodeWebp(string $abs, ?string $mime, int $maxW, int $quality, string $suffix): void
    {
        if (! function_exists('imagewebp') || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return;
        }
        try {
            $src = match ($mime) {
                'image/png'  => @imagecreatefrompng($abs),
                'image/webp' => @imagecreatefromwebp($abs),
                default      => @imagecreatefromjpeg($abs),
            };
            if (! $src) {
                return;
            }
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w > $maxW) {
                $nh  = (int) round($h * $maxW / $w);
                $dst = imagecreatetruecolor($maxW, $nh);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $nh, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }
            $out = $this->sibling($abs, $suffix);
            if ($out !== null) {
                imagewebp($src, $out, $quality);
            }
            imagedestroy($src);
        } catch (\Throwable $e) {
            // Optimization is best-effort; the original always remains usable.
        }
    }
}
