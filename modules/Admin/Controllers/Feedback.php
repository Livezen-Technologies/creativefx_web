<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Core\Libraries\Mailer;
use Modules\Tshda\Models\FeedbackModel;

/**
 * The public feedback and petition queue (Clause 3.9 J.a.iii, Clause 3.14).
 *
 * The closed loop: every submission arrives open with a due date, is assigned
 * to a division, is answered, and is closed. The counters at the top are what
 * the CMT watches — and "overdue" is measured against the due date the
 * submission was stamped with, so it is a fact rather than an impression.
 */
class Feedback extends BaseController
{
    public const STATUSES = ['open', 'assigned', 'answered', 'closed'];

    public function index()
    {
        helper('norlanka');

        $filters = [
            'status' => trim((string) $this->request->getGet('status')),
            'kind'   => trim((string) $this->request->getGet('kind')),
            'q'      => trim((string) $this->request->getGet('q')),
        ];

        return view('Modules\Admin\Views\feedback', [
            'title'       => 'Feedback & petitions',
            'active'      => 'feedback',
            'submissions' => (new FeedbackModel())->queue($filters),
            'filters'     => $filters,
            'statuses'    => self::STATUSES,
            'stats'       => (new FeedbackModel())->loopStats(),
        ]);
    }

    public function show(int $id)
    {
        helper('norlanka');

        $submission = (new FeedbackModel())->find($id);
        if ($submission === null) {
            return redirect()->to(site_url('admin/feedback'))->with('error', 'That submission no longer exists.');
        }

        return view('Modules\Admin\Views\feedback_show', [
            'title'      => 'Submission ' . $submission['reference'],
            'active'     => 'feedback',
            'submission' => $submission,
            'statuses'   => self::STATUSES,
        ]);
    }

    public function update(int $id)
    {
        $model      = new FeedbackModel();
        $submission = $model->find($id);
        if ($submission === null) {
            return redirect()->to(site_url('admin/feedback'))->with('error', 'That submission no longer exists.');
        }

        $status = (string) $this->request->getPost('status');
        if (! in_array($status, self::STATUSES, true)) {
            return redirect()->back()->with('error', 'Unknown status.');
        }

        $response = trim((string) $this->request->getPost('response'));

        $data = [
            'status'      => $status,
            'division'    => trim((string) $this->request->getPost('division')),
            'response'    => $response,
            'assigned_to' => session()->get('admin_user')['id'] ?? null,
        ];

        // The answered date is stamped when an answer is first given, and never
        // moved afterwards: it is when the citizen was answered, not when the
        // record was last touched.
        if ($response !== '' && empty($submission['answered_at'])) {
            $data['answered_at'] = date('Y-m-d H:i:s');
        }

        $model->update($id, $data);

        if ($response !== '' && $response !== (string) $submission['response']) {
            $this->notify($submission, $response);
        }

        return redirect()->to(site_url('admin/feedback/' . $id))->with('message', 'Submission updated.');
    }

    /**
     * Serve an attachment. Attachments live outside the web root, so this is
     * the only route to one, and it is behind the admin session.
     */
    public function attachment(int $id)
    {
        $submission = (new FeedbackModel())->find($id);
        if ($submission === null || empty($submission['attachment'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $path = WRITEPATH . 'uploads/' . ltrim((string) $submission['attachment'], '/');
        if (! is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($path, null)
            ->setFileName($submission['reference'] . '.' . pathinfo($path, PATHINFO_EXTENSION));
    }

    private function notify(array $submission, string $response): void
    {
        $email = trim((string) $submission['email']);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mailer::send(
                $email,
                'Your submission ' . $submission['reference'],
                view('Modules\Tshda\Views\emails\feedback_response', [
                    'reference' => $submission['reference'],
                    'subject'   => (string) $submission['subject'],
                    'response'  => $response,
                ], ['saveData' => false])
            );
        } catch (\Throwable $e) {
            log_message('error', 'Feedback response notification failed: ' . $e->getMessage());
        }
    }
}
