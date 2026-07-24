<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Media library (blueprint §16): bulk upload into per-folder directories under
 * public/media, searchable by name/tag/type, with an automatic optimized WebP
 * copy for images. Uploaded paths are pasted into block content, products,
 * news, downloads and video settings.
 */
class Media extends BaseController
{
    private const TYPE_FILTERS = ['image', 'video', 'document', '3d'];

    public function index()
    {
        $model = model('Modules\Media\Models\MediaModel');
        $q     = trim((string) $this->request->getGet('q'));
        $type  = (string) $this->request->getGet('type');

        $builder = $model->orderBy('id', 'DESC');
        if ($q !== '') {
            $builder->groupStart()
                ->like('path', $q)->orLike('tags', $q)->orLike('folder', $q)->orLike('mime_type', $q)
                ->groupEnd();
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

        return view('Modules\Admin\Views\media', [
            'title'  => 'Media',
            'active' => 'media',
            'rows'   => $builder->findAll(400),
            'q'      => $q,
            'type'   => $type,
            'types'  => self::TYPE_FILTERS,
        ]);
    }

    public function upload()
    {
        $files = $this->request->getFileMultiple('files') ?: [];
        $files = array_filter($files, static fn ($f) => $f !== null && $f->isValid() && ! $f->hasMoved());
        if ($files === []) {
            return redirect()->to(site_url('admin/media'))->with('error', 'No valid files uploaded.');
        }

        $folder = strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string) $this->request->getPost('folder'))) ?: 'uploads';
        $tags   = trim((string) $this->request->getPost('tags'));

        $count = 0;
        foreach ($files as $file) {
            $this->storeFile($file, $folder, $tags);
            $count++;
        }

        return redirect()->to(site_url('admin/media'))->with('message', $count . ' file(s) uploaded to media/' . $folder . '.');
    }

    /**
     * JSON upload endpoint for the rich-text editor and drag-and-drop fields.
     * Accepts a single "file"; returns {url, path, id} (WebP URL when one was
     * generated for an image, so embeds stay light).
     */
    public function uploadAjax()
    {
        $file = $this->request->getFile('file');
        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'No valid file received.']);
        }

        $folder = strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string) $this->request->getPost('folder'))) ?: 'uploads';
        $row    = $this->storeFile($file, $folder, trim((string) $this->request->getPost('tags')));

        // Tokens rotate per POST (Security::$regenerate) — hand the client the
        // fresh one so consecutive uploads keep working.
        $row['csrf'] = csrf_hash();

        return $this->response->setJSON($row);
    }

    /** Move an uploaded file into public/media/{folder}, register it, return its record. */
    private function storeFile($file, string $folder, string $tags = ''): array
    {
        $dir = FCPATH . 'media/' . $folder;
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = $file->getRandomName();
        $size = $file->getSize();
        $mime = $file->getMimeType();
        $file->move($dir, $name);
        $abs = $dir . '/' . $name;

        [$width, $height] = $this->imageMeta($abs, $mime);
        $this->makeWebpCopy($abs, $mime);

        $model = model('Modules\Media\Models\MediaModel');
        $id    = $model->insert([
            'disk'        => 'local',
            'path'        => 'media/' . $folder . '/' . $name,
            'url'         => '/media/' . $folder . '/' . $name,
            'mime_type'   => $mime,
            'size_bytes'  => $size,
            'width'       => $width,
            'height'      => $height,
            'folder'      => $folder,
            'tags'        => $tags !== '' ? $tags : null,
            'uploaded_by' => session()->get('admin_user')['id'] ?? null,
        ]);

        // Prefer the optimized WebP for embedding when it exists.
        $webp = preg_replace('/\.[a-z0-9]+$/i', '.webp', $abs);
        $url  = ($webp !== $abs && is_file($webp))
            ? '/media/' . $folder . '/' . preg_replace('/\.[a-z0-9]+$/i', '.webp', $name)
            : '/media/' . $folder . '/' . $name;

        return ['id' => $id, 'url' => $url, 'original' => '/media/' . $folder . '/' . $name, 'mime' => $mime];
    }

    public function delete($id)
    {
        $model = model('Modules\Media\Models\MediaModel');
        $row   = $model->find($id);
        if ($row !== null) {
            $abs = FCPATH . ($row['path'] ?? '');
            if (is_file($abs)) {
                @unlink($abs);
            }
            // Remove the generated WebP sibling too, if any.
            $webp = preg_replace('/\.[a-z0-9]+$/i', '.webp', $abs);
            if ($webp !== $abs && is_file($webp)) {
                @unlink($webp);
            }
            $model->delete($id);
        }
        return redirect()->to(site_url('admin/media'))->with('message', 'File deleted.');
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
        if (! function_exists('imagewebp') || ! in_array($mime, ['image/jpeg', 'image/png'], true)) {
            return;
        }
        try {
            $src = $mime === 'image/png' ? @imagecreatefrompng($abs) : @imagecreatefromjpeg($abs);
            if (! $src) {
                return;
            }
            $w = imagesx($src);
            $h = imagesy($src);
            if ($w > 1920) {
                $nh  = (int) round($h * 1920 / $w);
                $dst = imagecreatetruecolor(1920, $nh);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, 1920, $nh, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }
            imagewebp($src, preg_replace('/\.[a-z0-9]+$/i', '.webp', $abs), 80);
            imagedestroy($src);
        } catch (\Throwable $e) {
            // Optimization is best-effort; the original always remains usable.
        }
    }
}
