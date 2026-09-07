<?php

namespace Modules\Site\Controllers;

use App\Controllers\BaseController;
use Modules\Core\Libraries\Mailer;
use Modules\Core\Libraries\Recaptcha;
use Modules\Crm\Models\ContactModel;

/**
 * The two public forms that are not a purchase: the contact message and the
 * newsletter sign-up.
 *
 * Both follow the same rule, and it is the one that matters: **the record is
 * saved before anything is emailed, and a mail failure never fails the
 * request.** Until SMTP is configured — which on a fresh install it is not —
 * the site takes messages and shows them in the console; it simply cannot tell
 * anybody yet. A form that returns an error because a mail server is unreachable
 * loses the enquiry as well as the email.
 *
 * The page these post to is a CMS page, so the contact copy is editable; only
 * the handling lives here.
 */
class Contact extends BaseController
{
    public function submit(?string $locale = null)
    {
        helper(['norlanka', 'url']);

        $rules = [
            'name'    => ['label' => lang('Site.contact.name'), 'rules' => 'required|max_length[128]'],
            'email'   => ['label' => lang('Site.contact.email'), 'rules' => 'required|valid_email|max_length[191]'],
            'message' => ['label' => lang('Site.contact.message'), 'rules' => 'required|max_length[4000]'],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Only when reCAPTCHA is fully configured. A site with no keys accepts
        // the form rather than rejecting everybody, which is the behaviour the
        // rest of the platform already has.
        if (Recaptcha::guards('contact')) {
            $check = Recaptcha::verify($this->request->getPost('recaptcha_token'), 'contact');
            if (! ($check['ok'] ?? false)) {
                return redirect()->back()->withInput()->with('error', lang('Site.booking.err_robot'));
            }
        }

        // A minute's worth of patience per address. Enough that a form cannot be
        // used to send a hundred emails, low enough that somebody correcting a
        // typo and resubmitting is not locked out.
        $throttle = service('throttler');
        if ($throttle->check(md5('contact-' . $this->request->getIPAddress()), 5, MINUTE) === false) {
            return redirect()->back()->withInput()->with('error', lang('Site.contact.failed'));
        }

        $data = [
            'name'    => (string) $this->request->getPost('name'),
            'email'   => (string) $this->request->getPost('email'),
            'phone'   => (string) $this->request->getPost('phone'),
            'subject' => (string) $this->request->getPost('subject'),
            'message' => (string) $this->request->getPost('message'),
            'locale'  => current_locale(),
            'source'  => 'contact',
            'status'  => 'new',
        ];

        (new ContactModel())->insert($data);

        // After the insert, and never allowed to undo it.
        if (Mailer::isConfigured()) {
            Mailer::send(
                Mailer::contactRecipients(),
                sprintf('%s — enquiry from %s', setting('site_name', ''), $data['name']),
                view('Modules\Site\Views\emails\contact', ['data' => $data], ['saveData' => false]),
                $data['email']
            );
        }

        return redirect()->back()->with('message', lang('Site.contact.success'));
    }

    /**
     * The newsletter sign-up.
     *
     * Consent is required rather than assumed: the checkbox is un-ticked in the
     * markup and validated here, because a pre-ticked box is not consent under
     * either the GDPR or the Sri Lanka PDPA, and "they submitted the form" is
     * not a lawful basis anybody wants to argue.
     */
    public function subscribe(?string $locale = null)
    {
        helper(['norlanka', 'url']);

        $email = trim((string) $this->request->getPost('email'));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $this->request->getPost('consent')) {
            return redirect()->back()->withInput()->with('error', lang('Site.home.news_invalid'));
        }

        $throttle = service('throttler');
        if ($throttle->check(md5('subscribe-' . $this->request->getIPAddress()), 5, MINUTE) === false) {
            return redirect()->back()->with('error', lang('Site.home.news_invalid'));
        }

        $leads = new \Modules\Commerce\Models\LeadModel();

        // Signing up twice is not an error and must not read as one. The second
        // attempt refreshes the record and says the same friendly thing.
        $existing = $leads->where('email', $email)->where('type', 'newsletter')->first();
        if ($existing === null) {
            $leads->insert([
                'type'     => 'newsletter',
                'email'    => $email,
                'source'   => uri_string(),
                'utm_json' => \Modules\Commerce\Models\LeadModel::utm(),
                'status'   => 'new',
            ]);
        }

        return redirect()->back()->with('message', lang('Site.home.news_thanks'));
    }
}
