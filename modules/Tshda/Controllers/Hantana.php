<?php

namespace Modules\Tshda\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Core\Libraries\Mailer;
use Modules\Core\Libraries\Recaptcha;
use Modules\Tshda\Libraries\Reference;
use Modules\Tshda\Models\BookingModel;
use Modules\Tshda\Models\ProgrammeModel;

/**
 * The Hantana National Training Centre booking system (Clause 3.1.IV).
 *
 * A public calendar of programmes and intakes, an application form in all three
 * languages, capacity control that closes a programme when it is full, an
 * acknowledgement to the applicant and a notification to the responsible
 * officer — with the administrative queue itself in the console.
 */
class Hantana extends BaseController
{
    public function index(?string $locale = null)
    {
        helper('norlanka');

        return view('Modules\Tshda\Views\hantana\index', [
            'programmes'      => (new ProgrammeModel())->calendar(),
            'title'           => lang('Site.hantana.title') . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Site.hantana.meta'),
        ]);
    }

    public function show(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $programme = (new ProgrammeModel())->findLive((string) $slug);
        if ($programme === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('Modules\Tshda\Views\hantana\show', [
            'programme'       => $programme,
            'open'            => ProgrammeModel::isOpen($programme),
            'seatsLeft'       => ProgrammeModel::seatsLeft($programme),
            'title'           => t_field($programme['title']) . ' — ' . setting('site_name', ''),
            'metaDescription' => t_field($programme['summary']),
        ]);
    }

    public function apply(?string $locale = null, ?string $slug = null)
    {
        helper('norlanka');

        $model     = new ProgrammeModel();
        $programme = $model->findLive((string) $slug);
        if ($programme === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        // Honeypot: bots fill every field; a person never sees this one.
        if (trim((string) $this->request->getPost('website')) !== '') {
            return redirect()->to(locale_url('hantana/' . $slug))->with('booking_ok', '—');
        }

        if (! ProgrammeModel::isOpen($programme)) {
            return redirect()->back()->withInput()
                ->with('booking_error', lang('Site.hantana.err_closed'));
        }

        $rules = [
            'name'         => 'required|min_length[2]|max_length[191]',
            'phone'        => 'required|min_length[6]|max_length[64]',
            'email'        => 'permit_empty|valid_email|max_length[128]',
            'nic'          => 'permit_empty|max_length[32]',
            'district'     => 'permit_empty|max_length[64]',
            'participants' => 'required|is_natural_no_zero|less_than_equal_to[50]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('booking_errors', $this->validator->getErrors());
        }

        if (Recaptcha::guards('booking')) {
            $check = Recaptcha::verify($this->request->getPost('recaptcha_token'), 'booking');
            if (! $check['ok']) {
                log_message('warning', 'Training application refused by reCAPTCHA: ' . $check['reason']);

                return redirect()->back()->withInput()
                    ->with('booking_error', lang('Site.booking.err_robot'));
            }
        }

        $participants = (int) $this->request->getPost('participants');
        $seatsLeft    = ProgrammeModel::seatsLeft($programme);

        // Over-subscription is not refused outright: the party is recorded and
        // waitlisted, because a smallholder who has filled in the form should
        // not be told to try again and given nothing. The officer decides.
        $status = ($seatsLeft !== null && $participants > $seatsLeft) ? 'waitlisted' : 'pending';

        $bookings  = new BookingModel();
        $reference = Reference::generate('HTC', static fn (string $r): bool => $bookings->findByReference($r) !== null);

        $bookings->insert([
            'programme_id' => (int) $programme['id'],
            'reference'    => $reference,
            'name'         => trim((string) $this->request->getPost('name')),
            'nic'          => trim((string) $this->request->getPost('nic')),
            'email'        => trim((string) $this->request->getPost('email')),
            'phone'        => trim((string) $this->request->getPost('phone')),
            'address'      => trim((string) $this->request->getPost('address')),
            'district'     => trim((string) $this->request->getPost('district')),
            'society'      => trim((string) $this->request->getPost('society')),
            'participants' => $participants,
            'residential'  => $this->request->getPost('residential') ? 1 : 0,
            'notes'        => trim((string) $this->request->getPost('notes')),
            'locale'       => current_locale(),
            'status'       => $status,
            'ip_hash'      => Reference::ipHash($this->request->getIPAddress()),
        ]);

        // Saved first, notified second. A mail server that is down must never
        // be able to lose an application that has already been filled in.
        $this->notify($programme, $reference, $status);

        return redirect()->to(locale_url('hantana/' . $programme['slug']))
            ->with('booking_ok', $reference)
            ->with('booking_status', $status);
    }

    private function notify(array $programme, string $reference, string $status): void
    {
        helper('norlanka');

        try {
            $officer = Mailer::bookingRecipients();
            if ($officer !== '') {
                Mailer::send(
                    $officer,
                    'Training application ' . $reference . ' — ' . t_field($programme['title']),
                    view('Modules\Tshda\Views\emails\booking_officer', [
                        'reference' => $reference,
                        'programme' => $programme,
                        'status'    => $status,
                        'post'      => $this->request->getPost(),
                    ])
                );
            }

            // The acknowledgement Clause 3.6 asks for. Sent only when the
            // applicant gave an address — many smallholders will not have one,
            // and the reference on the confirmation screen is their receipt.
            $applicant = trim((string) $this->request->getPost('email'));
            if ($applicant !== '' && filter_var($applicant, FILTER_VALIDATE_EMAIL)) {
                Mailer::send(
                    $applicant,
                    'Your application to the Hantana National Training Centre — ' . $reference,
                    view('Modules\Tshda\Views\emails\booking_applicant', [
                        'reference' => $reference,
                        'programme' => $programme,
                        'status'    => $status,
                    ])
                );
            }
        } catch (\Throwable $e) {
            log_message('error', 'Training application notification failed: ' . $e->getMessage());
        }
    }
}
