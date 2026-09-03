<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Auth\Models\UserModel;
use Modules\Tshda\Libraries\Reference;
use Modules\Tshda\Models\DocumentModel;
use Modules\Tshda\Models\OfficeModel;
use Modules\Tshda\Models\OfficerSubmissionModel;
use Modules\Tshda\Models\ProgrammeModel;
use Modules\Tshda\Models\ServiceModel;

/**
 * The Field Officer Portal (Clause 3.1.II, proposal section 3.5).
 *
 * Authenticated access for registered officers to submit applications with
 * attachments, retrieve information relevant to their division and role, and
 * upload files. Accounts are provisioned and revoked by the Administrator in
 * the console — there is deliberately no self-registration, because the
 * population of this portal is the Authority's own staff and not the public.
 */
class FieldOfficer extends BaseController
{
    /** What an officer can send in. Each is a form the division expects. */
    public const KINDS = [
        'field_report'      => 'Field inspection report',
        'subsidy_recommend' => 'Subsidy application recommendation',
        'society_return'    => 'Society return',
        'nursery_inspection' => 'Nursery inspection',
        'stock_return'      => 'Fertilizer stock and issue return',
        'other'             => 'Other',
    ];

    /** The portal's front door: the login form, or the dashboard if signed in. */
    public function index(?string $locale = null)
    {
        helper('norlanka');

        if (session()->get('field_officer')) {
            return redirect()->to(locale_url('field-officer/dashboard'));
        }

        return view('Modules\Tshda\Views\officer\login', [
            'title'           => lang('Site.officer.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.officer.meta'),
            // A staff login page has nothing for a search engine and everything
            // for somebody enumerating an attack surface.
            'noIndex'         => true,
        ]);
    }

    public function login(?string $locale = null)
    {
        helper('norlanka');

        $email    = trim((string) $this->request->getPost('email'));
        $password = (string) $this->request->getPost('password');

        $users = new UserModel();
        $user  = $email === '' ? null : $users->findByEmail($email);

        // One message for a wrong address and a wrong password, and the hash is
        // verified even when no user was found, so the response neither says
        // which accounts exist nor answers faster when one does not.
        $hash  = $user['password_hash'] ?? '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidinv';
        $valid = password_verify($password, $hash) && $user !== null && ($user['status'] ?? '') === 'active';

        if ($valid) {
            $roles = $users->roleSlugs((int) $user['id']);
            // Administrators can use the portal too — they are the people who
            // will be asked to reproduce an officer's problem.
            $valid = array_intersect($roles, ['field-officer', 'super-admin']) !== [];
        }

        if (! $valid) {
            return redirect()->to(locale_url('field-officer'))
                ->with('officer_error', lang('Site.officer.err_credentials'));
        }

        // A new session ID on privilege change, so a session fixed before the
        // login cannot be used after it (Clause 3.16, session poisoning).
        session()->regenerate(true);
        session()->set('field_officer', [
            'id'        => (int) $user['id'],
            'name'      => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['email'],
            'email'     => $user['email'],
            'office_id' => $user['office_id'] ?? null,
        ]);
        $users->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        $intended = session()->get('officer_intended');
        session()->remove('officer_intended');

        return redirect()->to($intended ?: locale_url('field-officer/dashboard'));
    }

    public function logout(?string $locale = null)
    {
        helper('norlanka');
        session()->remove(['field_officer', 'officer_intended']);
        session()->regenerate(true);

        return redirect()->to(locale_url('field-officer'))->with('officer_ok', lang('Site.officer.signed_out'));
    }

    public function dashboard(?string $locale = null)
    {
        helper('norlanka');

        $officer = session()->get('field_officer');
        $office  = ! empty($officer['office_id'])
            ? (new OfficeModel())->find((int) $officer['office_id'])
            : null;

        return view('Modules\Tshda\Views\officer\dashboard', [
            'officer'         => $officer,
            'office'          => $office,
            'submissions'     => (new OfficerSubmissionModel())->forUser((int) $officer['id'], 20),
            'kinds'           => self::KINDS,
            // The "retrieve real-time information" half of Clause 3.1.II: the
            // circulars, forms and programme dates an officer in the field is
            // most often asked for, in one place rather than five.
            'forms'           => (new DocumentModel())->live()->findAll(10),
            'services'        => (new ServiceModel())->live()->findAll(),
            'programmes'      => (new ProgrammeModel())->calendar(5),
            'title'           => lang('Site.officer.dashboard') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.officer.meta'),
            'noIndex'         => true,
        ]);
    }

    public function submit(?string $locale = null)
    {
        helper('norlanka');

        $officer = session()->get('field_officer');

        $rules = [
            'kind'    => 'required',
            'subject' => 'required|min_length[3]|max_length[255]',
            'body'    => 'required|min_length[10]|max_length[8000]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $kind = (string) $this->request->getPost('kind');
        if (! array_key_exists($kind, self::KINDS)) {
            $kind = 'other';
        }

        $attachment = $this->storeAttachment();
        if ($attachment === false) {
            return redirect()->back()->withInput()->with('error', lang('Site.officer.err_file'));
        }

        $model     = new OfficerSubmissionModel();
        $reference = Reference::generate('FOP', static fn (string $r): bool => $model->where('reference', $r)->first() !== null);

        $model->insert([
            'reference'       => $reference,
            'user_id'         => (int) $officer['id'],
            // Taken from the session, never from the form: an officer must not
            // be able to file a return against another region by editing a
            // hidden field (Clause 3.16, parameter tampering).
            'office_id'       => $officer['office_id'] ?? null,
            'kind'            => $kind,
            'subject'         => trim((string) $this->request->getPost('subject')),
            'body'            => trim((string) $this->request->getPost('body')),
            'attachment'      => $attachment['path'] ?? null,
            'attachment_name' => $attachment['name'] ?? null,
            'status'          => 'submitted',
        ]);

        return redirect()->to(locale_url('field-officer/dashboard'))
            ->with('officer_ok', lang('Site.officer.submitted', [$reference]));
    }

    /**
     * Download an officer's own attachment.
     *
     * Attachments live outside the web root, so this is the only way to them,
     * and the ownership check is on the row rather than on the file name — an
     * officer may read their own submissions and nobody else's.
     */
    public function attachment(?string $locale = null, ?string $reference = null)
    {
        $officer    = session()->get('field_officer');
        $submission = (new OfficerSubmissionModel())->where('reference', (string) $reference)->first();

        if ($submission === null
            || (int) $submission['user_id'] !== (int) $officer['id']
            || empty($submission['attachment'])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $path = WRITEPATH . 'uploads/' . ltrim((string) $submission['attachment'], '/');
        if (! is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($path, null)
            ->setFileName((string) ($submission['attachment_name'] ?: basename($path)));
    }

    /**
     * @return array{path:string,name:string}|null|false
     */
    private function storeAttachment()
    {
        $file = $this->request->getFile('attachment');
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'xls', 'xlsx', 'csv', 'doc', 'docx'];
        $allowedMime = [
            'application/pdf', 'image/jpeg', 'image/png', 'text/plain', 'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if ($file->getSize() > 10 * 1024 * 1024) {
            return false;
        }
        if (! in_array(strtolower($file->getClientExtension()), $allowedExt, true)) {
            return false;
        }
        if (! in_array($file->getMimeType(), $allowedMime, true)) {
            return false;
        }

        $dir = WRITEPATH . 'uploads/officer/' . date('Y/m');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return false;
        }

        $stored = $file->getRandomName();
        $client = $file->getClientName();
        try {
            $file->move($dir, $stored);
        } catch (\Throwable $e) {
            log_message('error', 'Officer attachment could not be stored: ' . $e->getMessage());

            return false;
        }

        return [
            'path' => 'officer/' . date('Y/m') . '/' . $stored,
            'name' => mb_substr(preg_replace('/[^\p{L}\p{N}\-_. ]+/u', '', $client), 0, 191),
        ];
    }
}
