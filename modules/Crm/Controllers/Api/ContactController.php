<?php

namespace Modules\Crm\Controllers\Api;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Modules\Crm\Models\ContactModel;

/**
 * Public contact-form endpoint. Validates and stores submissions in the
 * `contacts` table (CRM module). Returns JSON so the front-end can submit
 * without a page reload.
 */
class ContactController extends ResourceController
{
    use ResponseTrait;

    public function submit()
    {
        // Honeypot: bots fill the hidden "website" field. Pretend success.
        if (trim((string) $this->request->getVar('website')) !== '') {
            return $this->respond(['status' => 'success']);
        }

        $rules = [
            'name'    => 'required|min_length[2]|max_length[128]',
            'email'   => 'required|valid_email|max_length[191]',
            'message' => 'required|min_length[5]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        if (\Modules\Core\Libraries\Recaptcha::guards('contact')) {
            $check = \Modules\Core\Libraries\Recaptcha::verify($this->request->getVar('recaptcha_token'), 'contact');
            if (! $check['ok']) {
                log_message('warning', 'Message refused by reCAPTCHA: ' . $check['reason']);

                return $this->failValidationErrors(['name' => lang('Site.booking.err_robot')]);
            }
        }

        $message = [
            'name'    => $this->request->getVar('name'),
            'email'   => $this->request->getVar('email'),
            'phone'   => $this->request->getVar('phone'),
            'subject' => $this->request->getVar('subject'),
            'message' => $this->request->getVar('message'),
            'locale'  => substr((string) $this->request->getVar('locale'), 0, 5) ?: 'en',
            'source'  => 'contact_form',
            'status'  => 'new',
        ];

        (new ContactModel())->insert($message);

        // Saved first, notified second — for the same reason as a booking: a
        // mail server being down must not be able to lose somebody's message.
        $this->notify($message);

        return $this->respondCreated(['status' => 'success']);
    }

    /** Pass a message on to whoever is listed, if mail is configured at all. */
    private function notify(array $message): void
    {
        try {
            $to = \Modules\Core\Libraries\Mailer::contactRecipients();
            if ($to === '' || ! \Modules\Core\Libraries\Mailer::isConfigured()) {
                return;
            }

            $html = '<h2 style="margin:0 0 16px">New message from the website</h2>'
                . '<table cellpadding="6" style="border-collapse:collapse">'
                . '<tr><td style="color:#666">Name</td><td><strong>' . esc((string) $message['name']) . '</strong></td></tr>'
                . '<tr><td style="color:#666">Email</td><td><strong>' . esc((string) $message['email']) . '</strong></td></tr>'
                . '<tr><td style="color:#666">Phone</td><td><strong>' . esc((string) ($message['phone'] ?: '—')) . '</strong></td></tr>'
                . '<tr><td style="color:#666">Subject</td><td><strong>' . esc((string) ($message['subject'] ?: '—')) . '</strong></td></tr>'
                . '</table>'
                . '<p style="margin-top:16px">' . nl2br(esc((string) $message['message'])) . '</p>'
                . '<p style="margin-top:20px;color:#666;font-size:12px">Reply to this message to answer them directly.</p>';

            $result = \Modules\Core\Libraries\Mailer::send(
                $to,
                'Website message — ' . $message['name'],
                $html,
                (string) $message['email'],
            );

            if (! $result['sent']) {
                log_message('error', 'Contact notification not sent: ' . ($result['detail'] ?: $result['error']));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Contact notification threw: ' . $e->getMessage());
        }
    }
}
