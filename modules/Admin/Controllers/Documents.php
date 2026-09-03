<?php

namespace Modules\Admin\Controllers;

use Modules\Tshda\Libraries\DocumentText;
use Modules\Tshda\Models\DocumentCategoryModel;
use Modules\Tshda\Models\DocumentModel;

/**
 * The document repository (Clause 3.9 H).
 *
 * Two things happen on save that do not happen on the other CRUD screens.
 *
 * The file's size and type are read off the file rather than typed, so the
 * listing cannot advertise "2 MB, PDF" about a file that is neither.
 *
 * And the text inside the file is extracted and stored, because Clause 3.12
 * asks for search to reach inside a circular rather than only at its name. A
 * scanned PDF has no text to extract; the editor is told so, rather than the
 * document silently becoming unfindable.
 */
class Documents extends BaseCrudController
{
    protected string $modelClass = DocumentModel::class;
    protected string $title      = 'Documents';
    protected string $singular   = 'Document';
    protected string $route      = 'documents';
    protected string $active     = 'documents';
    protected string $orderBy    = 'published_at';
    protected string $orderDir   = 'DESC';

    protected array $listColumns = [
        ['name' => 'title', 'label' => 'Document', 'type' => 'locale'],
        ['name' => 'file_type', 'label' => 'Type', 'type' => 'badge'],
        ['name' => 'published_at', 'label' => 'Published'],
        ['name' => 'expires_at', 'label' => 'Expires'],
        ['name' => 'download_count', 'label' => 'Downloads'],
        ['name' => 'status', 'label' => 'Status', 'type' => 'badge'],
    ];

    public function __construct()
    {
        $options = ['' => '— uncategorised —'];
        try {
            helper('norlanka');
            foreach ((new DocumentCategoryModel())->live() as $category) {
                $options[(string) $category['id']] = t_field($category['name']);
            }
        } catch (\Throwable $e) {
            // No table yet: the field renders with just the empty option.
        }

        $this->fields = [
            ['name' => 'title', 'label' => 'Title', 'type' => 'locale', 'rules' => 'required'],
            ['name' => 'slug', 'label' => 'Address', 'rules' => 'required|alpha_dash|max_length[191]'],
            ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => $options],
            ['name' => 'description', 'label' => 'What it is', 'type' => 'locale_textarea'],
            ['name' => 'file_path', 'label' => 'File', 'type' => 'file', 'folder' => 'documents', 'accept' => '.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt', 'rules' => 'permit_empty'],
            ['name' => 'language', 'label' => 'Language of the document', 'type' => 'select', 'options' => [
                'en' => 'English', 'si' => 'Sinhala', 'ta' => 'Tamil', 'all' => 'All three',
            ]],
            ['name' => 'published_at', 'label' => 'Publish from', 'placeholder' => 'YYYY-MM-DD HH:MM:SS', 'help' => 'Blank publishes immediately'],
            ['name' => 'expires_at', 'label' => 'Remove on', 'placeholder' => 'YYYY-MM-DD HH:MM:SS', 'help' => 'A tender’s closing date. The document drops off the listing, and stops downloading, on this date'],
            ['name' => 'download_count', 'label' => 'Downloads', 'type' => 'static'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived']],
        ];
    }

    protected function persist($id)
    {
        $rules = $this->rules();
        if ($rules !== [] && ! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data  = $this->collect();
        $model = $this->model();
        $note  = null;

        $path = trim((string) ($data['file_path'] ?? ''));
        if ($path !== '') {
            $absolute = FCPATH . ltrim($path, '/');
            if (is_file($absolute)) {
                $data['file_size'] = (int) filesize($absolute);
                $data['file_type'] = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));

                $extracted = DocumentText::extract($absolute);
                $data['extracted_text'] = $extracted['text'];

                $note = match ($extracted['method']) {
                    'scanned' => 'No text could be read from this file, so its contents will not be searchable. '
                        . 'It is almost certainly a scan; run it through OCR before uploading, or ask the ICT Officer to.',
                    'none'    => 'This file type carries no searchable text, so only its title and description are indexed.',
                    default   => null,
                };
            }
        }

        if ($id) {
            $model->update($id, $data);
        } else {
            $model->insert($data);
        }

        $redirect = redirect()->to(site_url('admin/' . $this->route))
            ->with('message', $this->singular . ' saved.');

        return $note === null ? $redirect : $redirect->with('error', $note);
    }
}
