<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Tshda\Models\DocumentCategoryModel;
use Modules\Tshda\Models\DocumentModel;

/**
 * The central document repository (Clause 3.9 H): search, category filter and
 * download counts over tenders, publications, Acts, regulations, annual
 * reports, application forms, standards and recruitment notices.
 */
class Downloads extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        $categories = (new DocumentCategoryModel())->live();
        $slug       = trim((string) $this->request->getGet('category'));
        $query      = trim((string) $this->request->getGet('q'));

        $activeCategory = null;
        if ($slug !== '') {
            foreach ($categories as $c) {
                if ($c['slug'] === $slug) {
                    $activeCategory = $c;
                    break;
                }
            }
            if ($activeCategory === null) {
                throw PageNotFoundException::forPageNotFound();
            }
        }

        $documents = (new DocumentModel())->search($query, $activeCategory['id'] ?? null, 200);

        return view('Modules\Tshda\Views\downloads', [
            'documents'       => $documents,
            'categories'      => $categories,
            'activeCategory'  => $activeCategory,
            'query'           => $query,
            'title'           => lang('Site.downloads.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.downloads.meta'),
        ]);
    }

    /**
     * Serve a document and count the download.
     *
     * Files are served through here rather than linked directly so that the
     * count Clause 3.9 H asks for is a fact rather than an estimate, and so
     * that an expired tender stops being reachable at its own URL the moment it
     * expires — an unlinked file that still downloads is still published.
     */
    public function file(?string $locale = null, ?string $slug = null)
    {
        $document = (new DocumentModel())->live()->where('slug', (string) $slug)->first();
        if ($document === null || empty($document['file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $path = FCPATH . ltrim((string) $document['file_path'], '/');
        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        (new DocumentModel())->recordDownload((int) $document['id']);

        helper('norlanka');
        $name = t_field($document['title']);
        $ext  = pathinfo($path, PATHINFO_EXTENSION);
        // A filename built from the title, so the file in the visitor's
        // downloads folder says what it is rather than "d_1043.pdf".
        $filename = trim(preg_replace('/[^\p{L}\p{N}\-_. ]+/u', '', $name)) ?: $document['slug'];

        return $this->response->download($path, null)
            ->setFileName($filename . ($ext !== '' ? '.' . $ext : ''));
    }
}
