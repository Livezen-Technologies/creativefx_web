<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Tshda\Models\OfficerSubmissionModel;

/** What field officers have sent in through the portal (Clause 3.1.II). */
class OfficerSubmissions extends BaseController
{
    public const STATUSES = ['submitted', 'received', 'actioned', 'returned'];

    public function index()
    {
        helper('norlanka');

        $status = trim((string) $this->request->getGet('status'));

        return view('Modules\Admin\Views\officer_submissions', [
            'title'       => 'Field officer submissions',
            'active'      => 'officers',
            'submissions' => (new OfficerSubmissionModel())->queue($status ?: null),
            'status'      => $status,
            'statuses'    => self::STATUSES,
        ]);
    }

    public function update(int $id)
    {
        $model = new OfficerSubmissionModel();
        if ($model->find($id) === null) {
            return redirect()->back()->with('error', 'That submission no longer exists.');
        }

        $status = (string) $this->request->getPost('status');
        if (! in_array($status, self::STATUSES, true)) {
            return redirect()->back()->with('error', 'Unknown status.');
        }

        $model->update($id, [
            'status'       => $status,
            'officer_note' => trim((string) $this->request->getPost('officer_note')),
            'reviewed_by'  => session()->get('admin_user')['id'] ?? null,
            'reviewed_at'  => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('message', 'Submission updated.');
    }

    public function attachment(int $id)
    {
        $submission = (new OfficerSubmissionModel())->find($id);
        if ($submission === null || empty($submission['attachment'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $path = WRITEPATH . 'uploads/' . ltrim((string) $submission['attachment'], '/');
        if (! is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($path, null)
            ->setFileName((string) ($submission['attachment_name'] ?: basename($path)));
    }
}
