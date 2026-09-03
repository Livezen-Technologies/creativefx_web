<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Core\Libraries\Mailer;
use Modules\Core\Libraries\Recaptcha;
use Modules\Tshda\Libraries\Reference;
use Modules\Tshda\Models\FeedbackModel;

/**
 * Public feedback, queries and petitions (Clause 3.9 J.a.iii, Clause 3.14).
 *
 * Every submission is given a reference number, stamped with a due date and
 * routed to a responsible officer, and the citizen can look it up again with
 * that reference. That is the whole point of the clause — "every submission is
 * queued to a responsible officer for later processing, with tracking so
 * nothing is lost" — and it is why this is a table with a workflow rather than
 * a form that sends an email.
 */
class Feedback extends BaseController
{
    /** The service standard: how long the Authority has to answer. */
    private const DUE_DAYS = 14;

    /** A hard ceiling on the message, as Clause 3.9 J.a.iii requires. */
    public const MAX_MESSAGE = 4000;

    public const KINDS = ['feedback', 'query', 'petition', 'complaint'];

    public function index(?string $locale = null)
    {
        helper('norlanka');

        return view('Modules\Tshda\Views\feedback\form', [
            'kinds'           => self::KINDS,
            'maxMessage'      => self::MAX_MESSAGE,
            'title'           => lang('Site.feedback.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.feedback.meta'),
        ]);
    }

    public function submit(?string $locale = null)
    {
        helper('norlanka');

        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to(locale_url('feedback'));
        }

        $rules = [
            'name'    => 'required|min_length[2]|max_length[191]',
            'email'   => 'permit_empty|valid_email|max_length[128]',
            'phone'   => 'permit_empty|max_length[64]',
            'subject' => 'required|min_length[3]|max_length[255]',
            'message' => 'required|min_length[10]|max_length[' . self::MAX_MESSAGE . ']',
            // Somebody has to be reachable, or an answer cannot be delivered.
            // Neither field alone can be required, because many smallholders
            // have a telephone and no email address.
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $email = trim((string) $this->request->getPost('email'));
        $phone = trim((string) $this->request->getPost('phone'));
        if ($email === '' && $phone === '') {
            return redirect()->back()->withInput()
                ->with('errors', ['email' => lang('Site.feedback.err_contact')]);
        }

        if (Recaptcha::guards('contact')) {
            $check = Recaptcha::verify($this->request->getPost('recaptcha_token'), 'feedback');
            if (! $check['ok']) {
                log_message('warning', 'Feedback refused by reCAPTCHA: ' . $check['reason']);

                return redirect()->back()->withInput()->with('error', lang('Site.booking.err_robot'));
            }
        }

        $kind = (string) $this->request->getPost('kind');
        if (! in_array($kind, self::KINDS, true)) {
            $kind = 'feedback';
        }

        $attachment = $this->storeAttachment();
        if ($attachment === false) {
            return redirect()->back()->withInput()->with('error', lang('Site.feedback.err_file'));
        }

        $model     = new FeedbackModel();
        $reference = Reference::generate('FBK', static fn (string $r): bool => $model->findByReference($r) !== null);

        $model->insert([
            'reference'  => $reference,
            'kind'       => $kind,
            'name'       => trim((string) $this->request->getPost('name')),
            'email'      => $email,
            'phone'      => $phone,
            'district'   => trim((string) $this->request->getPost('district')),
            'subject'    => trim((string) $this->request->getPost('subject')),
            'message'    => trim((string) $this->request->getPost('message')),
            'page_url'   => substr(trim((string) $this->request->getPost('page_url')), 0, 255),
            'attachment' => $attachment,
            'locale'     => current_locale(),
            'status'     => 'open',
            'due_at'     => date('Y-m-d H:i:s', strtotime('+' . self::DUE_DAYS . ' days')),
            'ip_hash'    => Reference::ipHash($this->request->getIPAddress()),
        ]);

        $this->notify($reference, $kind);

        return redirect()->to(locale_url('feedback'))->with('reference', $reference);
    }

    /** Look a submission up by the reference the citizen was given. */
    public function track(?string $locale = null)
    {
        helper('norlanka');

        $reference  = strtoupper(trim((string) $this->request->getGet('reference')));
        $submission = null;
        $notFound   = false;

        if ($reference !== '') {
            $submission = (new FeedbackModel())->findByReference($reference);
            $notFound   = $submission === null;
        }

        return view('Modules\Tshda\Views\feedback\track', [
            'reference'       => $reference,
            'submission'      => $submission,
            'notFound'        => $notFound,
            'title'           => lang('Site.feedback.track_title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.feedback.track_meta'),
        ]);
    }

    /**
     * Save an attached document, if one was sent.
     *
     * @return string|null|false the stored path, null when nothing was sent,
     *                           false when the file was refused
     */
    private function storeAttachment()
    {
        $file = $this->request->getFile('attachment');
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        // Type is checked by the extension the client claims AND by what the
        // file's own bytes say it is, because a client can claim anything.
        $allowedExt  = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        $allowedMime = [
            'application/pdf', 'image/jpeg', 'image/png',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];

        if ($file->getSize() > 5 * 1024 * 1024) {
            return false;
        }
        if (! in_array(strtolower($file->getClientExtension()), $allowedExt, true)) {
            return false;
        }
        if (! in_array($file->getMimeType(), $allowedMime, true)) {
            return false;
        }

        // Stored under writable/, never under public/: an attachment to a
        // petition is somebody's document, and a guessable public URL would
        // publish it. Officers read it through the authenticated console.
        $dir = WRITEPATH . 'uploads/feedback/' . date('Y/m');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return false;
        }

        $name = $file->getRandomName();
        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            log_message('error', 'Feedback attachment could not be stored: ' . $e->getMessage());

            return false;
        }

        return 'feedback/' . date('Y/m') . '/' . $name;
    }

    private function notify(string $reference, string $kind): void
    {
        try {
            $to = Mailer::contactRecipients();
            if ($to === '') {
                return;
            }

            Mailer::send(
                $to,
                ucfirst($kind) . ' ' . $reference . ' — ' . trim((string) $this->request->getPost('subject')),
                view('Modules\Tshda\Views\emails\feedback_officer', [
                    'reference' => $reference,
                    'kind'      => $kind,
                    'post'      => $this->request->getPost(),
                ]),
                trim((string) $this->request->getPost('email')) ?: null
            );
        } catch (\Throwable $e) {
            log_message('error', 'Feedback notification failed: ' . $e->getMessage());
        }
    }
}
