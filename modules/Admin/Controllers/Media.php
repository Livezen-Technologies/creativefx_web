<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Media library: upload files into public/media/uploads and record them in
 * media_library. Uploaded paths can then be pasted into block content / video
 * settings.
 */
class Media extends BaseController
{
    public function index()
    {
        return view('Modules\Admin\Views\media', [
            'title'  => 'Media',
            'active' => 'media',
            'rows'   => model('Modules\Media\Models\MediaModel')->orderBy('id', 'DESC')->findAll(),
        ]);
    }

    public function upload()
    {
        $file = $this->request->getFile('file');

        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->to(site_url('admin/media'))->with('error', 'No valid file uploaded.');
        }

        $dir = FCPATH . 'media/uploads';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $name = $file->getRandomName();
        $size = $file->getSize();
        $mime = $file->getMimeType();
        $file->move($dir, $name);

        model('Modules\Media\Models\MediaModel')->insert([
            'disk'        => 'local',
            'path'        => 'media/uploads/' . $name,
            'url'         => '/media/uploads/' . $name,
            'mime_type'   => $mime,
            'size_bytes'  => $size,
            'folder'      => 'uploads',
            'uploaded_by' => session()->get('admin_user')['id'] ?? null,
        ]);

        return redirect()->to(site_url('admin/media'))->with('message', 'File uploaded.');
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
            $model->delete($id);
        }
        return redirect()->to(site_url('admin/media'))->with('message', 'File deleted.');
    }
}
