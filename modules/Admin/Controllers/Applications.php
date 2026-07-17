<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Careers\Models\JobApplicationModel;

/**
 * HR recruitment dashboard: pipeline view of job applications with status
 * management (new → hired), internal notes, 1–5 rating, private CV download
 * and CSV export.
 */
class Applications extends BaseController
{
    public function index()
    {
        $model  = new JobApplicationModel();
        $status = (string) $this->request->getGet('status');

        $builder = $model->select('job_applications.*, jobs.title AS job_title, jobs.slug AS job_slug')
            ->join('jobs', 'jobs.id = job_applications.job_id', 'left')
            ->orderBy('job_applications.id', 'DESC');
        if (in_array($status, JobApplicationModel::STATUSES, true)) {
            $builder->where('job_applications.status', $status);
        }

        return view('Modules\Admin\Views\applications\index', [
            'title'    => 'Applications',
            'active'   => 'applications',
            'rows'     => $builder->findAll(),
            'counts'   => $model->countsByStatus(),
            'statuses' => JobApplicationModel::STATUSES,
            'current'  => $status,
        ]);
    }

    public function show($id)
    {
        $model = new JobApplicationModel();
        $row   = $model->select('job_applications.*, jobs.title AS job_title, jobs.slug AS job_slug')
            ->join('jobs', 'jobs.id = job_applications.job_id', 'left')
            ->where('job_applications.id', (int) $id)
            ->first();
        if ($row === null) {
            return redirect()->to(site_url('admin/applications'))->with('error', 'Application not found.');
        }

        return view('Modules\Admin\Views\applications\show', [
            'title'    => 'Application — ' . $row['name'],
            'active'   => 'applications',
            'row'      => $row,
            'statuses' => JobApplicationModel::STATUSES,
        ]);
    }

    public function update($id)
    {
        $model = new JobApplicationModel();
        if ($model->find((int) $id) === null) {
            return redirect()->to(site_url('admin/applications'))->with('error', 'Application not found.');
        }

        $status = (string) $this->request->getPost('status');
        $rating = $this->request->getPost('rating');
        $model->update((int) $id, [
            'status' => in_array($status, JobApplicationModel::STATUSES, true) ? $status : 'new',
            'notes'  => (string) $this->request->getPost('notes'),
            'rating' => $rating !== null && $rating !== '' ? max(1, min(5, (int) $rating)) : null,
        ]);

        return redirect()->to(site_url('admin/applications/' . (int) $id))->with('message', 'Application updated.');
    }

    /** Stream the privately-stored CV (never web-accessible directly). */
    public function download($id)
    {
        $row = (new JobApplicationModel())->find((int) $id);
        $abs = $row ? WRITEPATH . ($row['resume_path'] ?? '') : '';
        if ($row === null || $row['resume_path'] === null || ! is_file($abs)) {
            return redirect()->to(site_url('admin/applications'))->with('error', 'CV file not found.');
        }

        $ext = pathinfo($abs, PATHINFO_EXTENSION);
        return $this->response->download($abs, null)
            ->setFileName('CV-' . preg_replace('/[^A-Za-z0-9]+/', '-', $row['name']) . '.' . $ext);
    }

    /** Export the (optionally status-filtered) applicant list as CSV. */
    public function export()
    {
        $model  = new JobApplicationModel();
        $status = (string) $this->request->getGet('status');
        $builder = $model->select('job_applications.*, jobs.title AS job_title')
            ->join('jobs', 'jobs.id = job_applications.job_id', 'left')
            ->orderBy('job_applications.id', 'DESC');
        if (in_array($status, JobApplicationModel::STATUSES, true)) {
            $builder->where('job_applications.status', $status);
        }

        helper('norlanka');
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Applied', 'Job', 'Name', 'Email', 'Phone', 'Country', 'Education', 'Experience', 'Skills', 'LinkedIn', 'Status', 'Rating', 'Notes']);
        foreach ($builder->findAll() as $r) {
            fputcsv($out, [
                $r['id'], $r['created_at'],
                t_field(json_decode($r['job_title'] ?? '[]', true) ?: []),
                $r['name'], $r['email'], $r['phone'], $r['country'], $r['education'],
                $r['experience'], $r['skills'], $r['linkedin'], $r['status'], $r['rating'], $r['notes'],
            ]);
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="applications-' . date('Ymd-His') . '.csv"')
            ->setBody($csv);
    }
}
