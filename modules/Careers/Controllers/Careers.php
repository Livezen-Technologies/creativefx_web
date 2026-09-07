<?php

namespace Modules\Careers\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Careers\Models\JobApplicationModel;
use Modules\Careers\Models\JobModel;

/**
 * Public careers portal: vacancy detail page + online application submission
 * with CV upload. CVs are stored privately under WRITEPATH (never public/);
 * HR downloads them through the authenticated admin panel.
 */
class Careers extends BaseController
{
    /**
     * The vacancy list.
     *
     * The route for this has existed since the fork and pointed at a method
     * that did not, so /careers answered 404 while the site linked to it from
     * the footer. It is a short page, but a dead link in the footer of every
     * page is the kind of thing a reader reads as "nobody looks after this".
     *
     * When there is nothing open the page says so in one line rather than
     * hiding itself. A careers page that 404s when hiring pauses loses the
     * inbound links it spent a year earning, and "no vacancies at the moment"
     * is a real answer to the question the visitor arrived with.
     *
     * No speculative listings: the grid renders what is in the table and
     * nothing else. An invented vacancy is a person's afternoon.
     */
    public function index(?string $locale = null)
    {
        helper(['norlanka', 'url']);

        return view('Modules\Careers\Views\index', [
            'jobs'            => (new JobModel())->openJobs(),
            'title'           => lang('Site.careers.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.careers.meta'),
            'canonical'       => locale_url('careers'),
            // Home is emitted by the breadcrumb partial itself; adding it here
            // renders it twice.
            'crumbs'          => [['label' => lang('Site.careers.title')]],
        ]);
    }

    public function show(?string $locale = null, ?string $slug = null)
    {
        $job = (new JobModel())->findOpenBySlug((string) $slug);
        if ($job === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        helper('norlanka');

        return view('Modules\Careers\Views\job', [
            'job'             => $job,
            'title'           => t_field(json_decode($job['title'] ?? '[]', true) ?: []) . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.careers.meta'),
        ]);
    }

    public function apply(?string $locale = null, ?string $slug = null)
    {
        $job = (new JobModel())->findOpenBySlug((string) $slug);
        if ($job === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        // Honeypot: bots fill every field; humans never see this one.
        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->back();
        }

        $rules = [
            'name'  => 'required|min_length[2]|max_length[128]',
            'email' => 'required|valid_email|max_length[191]',
            'cv'    => 'uploaded[cv]|max_size[cv,5120]|ext_in[cv,pdf,doc,docx]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Private storage — never web-accessible.
        $dir = WRITEPATH . 'uploads/applications';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $cv = $this->request->getFile('cv');
        $cvName = $cv->getRandomName();
        $cv->move($dir, $cvName);

        (new JobApplicationModel())->insert([
            'job_id'       => (int) $job['id'],
            'name'         => (string) $this->request->getPost('name'),
            'email'        => (string) $this->request->getPost('email'),
            'phone'        => (string) $this->request->getPost('phone'),
            'country'      => (string) $this->request->getPost('country'),
            'education'    => (string) $this->request->getPost('education'),
            'experience'   => (string) $this->request->getPost('experience'),
            'skills'       => (string) $this->request->getPost('skills'),
            'linkedin'     => (string) $this->request->getPost('linkedin'),
            'cover_letter' => (string) $this->request->getPost('cover_letter'),
            'resume_path'  => 'uploads/applications/' . $cvName,
            'status'       => 'new',
        ]);

        return redirect()->back()->with('applied', true);
    }
}
