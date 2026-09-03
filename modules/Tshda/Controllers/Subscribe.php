<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use Modules\Core\Libraries\Mailer;
use Modules\Tshda\Models\SubscriberModel;

/**
 * Alert subscriptions (Clause 3.13): public subscription by email, with the
 * subscriber choosing categories of interest, double opt-in confirmation and
 * one-click unsubscribe.
 *
 * Double opt-in is not a formality here. Anyone can type anyone's address into
 * a public form; without a confirmation step the Authority would be mailing
 * strangers on the say-so of whoever filled the form in.
 */
class Subscribe extends BaseController
{
    public function create(?string $locale = null)
    {
        helper('norlanka');

        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->back();
        }

        $email = trim((string) $this->request->getPost('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('subscribe_error', lang('Site.alerts.err_email'));
        }

        $topics = array_values(array_intersect(
            (array) ($this->request->getPost('topics') ?? []),
            SubscriberModel::TOPICS
        ));

        $model    = new SubscriberModel();
        $existing = $model->where('email', $email)->first();

        // An address that is already confirmed is told so rather than sent a
        // second confirmation link — and its topic choices are updated, because
        // subscribing again with different boxes ticked plainly means "these".
        if ($existing !== null && $existing['status'] === 'active') {
            $model->update((int) $existing['id'], ['topics' => implode(',', $topics)]);

            return redirect()->back()->with('subscribe_ok', lang('Site.alerts.already'));
        }

        $token = bin2hex(random_bytes(24));

        if ($existing !== null) {
            $model->update((int) $existing['id'], [
                'topics' => implode(',', $topics),
                'locale' => current_locale(),
                'status' => 'pending',
                'token'  => $token,
            ]);
        } else {
            $model->insert([
                'email'  => $email,
                'name'   => trim((string) $this->request->getPost('name')),
                'locale' => current_locale(),
                'topics' => implode(',', $topics),
                'status' => 'pending',
                'token'  => $token,
            ]);
        }

        $this->sendConfirmation($email, $token);

        return redirect()->back()->with('subscribe_ok', lang('Site.alerts.check_inbox'));
    }

    public function confirm(?string $locale = null, ?string $token = null)
    {
        helper('norlanka');

        $model      = new SubscriberModel();
        $subscriber = $model->findByToken((string) $token);

        if ($subscriber !== null && $subscriber['status'] !== 'unsubscribed') {
            $model->update((int) $subscriber['id'], [
                'status'       => 'active',
                'confirmed_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return view('Modules\Tshda\Views\alerts\result', [
            'ok'              => $subscriber !== null,
            'mode'            => 'confirm',
            'title'           => lang('Site.alerts.confirm_title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.alerts.meta'),
            'noIndex'         => true,
        ]);
    }

    public function unsubscribe(?string $locale = null, ?string $token = null)
    {
        helper('norlanka');

        $model      = new SubscriberModel();
        $subscriber = $model->findByToken((string) $token);

        if ($subscriber !== null) {
            // Deleted, not flagged. "Unsubscribe" that keeps the address on
            // file is not what the word means, and a row that stays behind is a
            // row that gets mailed again by the next well-meaning change.
            $model->delete((int) $subscriber['id']);
        }

        return view('Modules\Tshda\Views\alerts\result', [
            'ok'              => $subscriber !== null,
            'mode'            => 'unsubscribe',
            'title'           => lang('Site.alerts.unsub_title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.alerts.meta'),
            'noIndex'         => true,
        ]);
    }

    private function sendConfirmation(string $email, string $token): void
    {
        helper('norlanka');

        try {
            Mailer::send(
                $email,
                'Confirm your alerts from the Tea Small Holdings Development Authority',
                view('Modules\Tshda\Views\emails\subscribe_confirm', [
                    'confirmUrl' => locale_url('alerts/confirm/' . $token),
                ])
            );
        } catch (\Throwable $e) {
            log_message('error', 'Subscription confirmation failed: ' . $e->getMessage());
        }
    }
}
