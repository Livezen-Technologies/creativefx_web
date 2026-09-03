<?php

namespace Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Modules\Core\Libraries\Mailer;
use Modules\Tshda\Models\BookingModel;
use Modules\Tshda\Models\ProgrammeModel;

/**
 * The Hantana booking queue (Clause 3.1.IV): confirm, waitlist or decline an
 * application, and tell the applicant the outcome.
 *
 * The confirmed count on the programme is maintained here rather than counted
 * on read, and it is maintained by *difference* — a booking moving into
 * confirmed adds its participants, one moving out subtracts them. Recounting
 * from the table on every change would be simpler and would also be a race
 * against every other officer working the same queue.
 */
class Bookings extends BaseController
{
    public const STATUSES = ['pending', 'confirmed', 'waitlisted', 'declined', 'cancelled'];

    public function index()
    {
        helper('norlanka');

        $status      = trim((string) $this->request->getGet('status'));
        $programmeId = (int) $this->request->getGet('programme') ?: null;

        $bookings = (new BookingModel())->queue($status ?: null, $programmeId);

        return view('Modules\Admin\Views\bookings', [
            'title'       => 'Training applications',
            'active'      => 'hantana',
            'bookings'    => $bookings,
            'programmes'  => (new ProgrammeModel())->orderBy('starts_on', 'DESC')->findAll(),
            'status'      => $status,
            'programmeId' => $programmeId,
            'statuses'    => self::STATUSES,
            'counts'      => $this->counts(),
        ]);
    }

    public function update(int $id)
    {
        $model   = new BookingModel();
        $booking = $model->find($id);
        if ($booking === null) {
            return redirect()->to(site_url('admin/bookings'))->with('error', 'That application no longer exists.');
        }

        $status = (string) $this->request->getPost('status');
        if (! in_array($status, self::STATUSES, true)) {
            return redirect()->back()->with('error', 'Unknown status.');
        }

        $was = (string) $booking['status'];
        $model->update($id, [
            'status'       => $status,
            'officer_note' => trim((string) $this->request->getPost('officer_note')),
            'handled_by'   => session()->get('admin_user')['id'] ?? null,
            'handled_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->adjustCapacity((int) $booking['programme_id'], (int) $booking['participants'], $was, $status);

        if ($was !== $status) {
            $this->notifyApplicant($booking, $status);
        }

        return redirect()->back()->with('message', 'Application ' . $booking['reference'] . ' updated.');
    }

    /** Confirmed places, adjusted by the difference rather than recounted. */
    private function adjustCapacity(int $programmeId, int $participants, string $was, string $now): void
    {
        if ($was === $now || $programmeId === 0) {
            return;
        }

        $delta = 0;
        if ($now === 'confirmed') {
            $delta = $participants;
        } elseif ($was === 'confirmed') {
            $delta = -$participants;
        }
        if ($delta === 0) {
            return;
        }

        $programmes = new ProgrammeModel();
        $programme  = $programmes->find($programmeId);
        if ($programme === null) {
            return;
        }

        // Floored at zero: a count that has drifted negative would reopen a
        // full programme, and it is better to be wrong in the safe direction.
        $programmes->update($programmeId, [
            'booked' => max(0, (int) $programme['booked'] + $delta),
        ]);
    }

    private function notifyApplicant(array $booking, string $status): void
    {
        $email = trim((string) $booking['email']);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $lines = [
            'confirmed'  => 'Your application has been confirmed. Joining instructions will follow.',
            'waitlisted' => 'Your application has been placed on the waiting list. We will contact you if a place becomes available.',
            'declined'   => 'We are sorry: your application has not been successful on this occasion.',
            'cancelled'  => 'Your application has been cancelled.',
            'pending'    => 'Your application is being considered.',
        ];

        try {
            Mailer::send(
                $email,
                'Your training application ' . $booking['reference'],
                view('Modules\Tshda\Views\emails\booking_outcome', [
                    'reference' => $booking['reference'],
                    'message'   => $lines[$status] ?? $lines['pending'],
                    'note'      => trim((string) $this->request->getPost('officer_note')),
                ], ['saveData' => false])
            );
        } catch (\Throwable $e) {
            log_message('error', 'Booking outcome notification failed: ' . $e->getMessage());
        }
    }

    /** @return array<string,int> */
    private function counts(): array
    {
        $out = [];
        foreach (self::STATUSES as $status) {
            $out[$status] = (new BookingModel())->where('status', $status)->countAllResults();
        }

        return $out;
    }
}
