<?php

namespace Modules\Crm\Controllers\Api;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\RESTful\ResourceController;
use Modules\Crm\Models\ContactModel;

/**
 * Room requests from the hero's booking modal.
 *
 * This site takes no payment and publishes no live availability, so a request
 * is exactly that: the guest says who they are and when they would like to
 * come, and staff confirm by phone or email. It lands in the same inbox as the
 * contact form, marked with its own source so it can be told apart.
 *
 * The dates are the part worth validating properly. A check-out before a
 * check-in, or an arrival in the past, is a typo the guest would rather hear
 * about now than after waiting two days for a reply that says the same thing.
 */
class BookingController extends ResourceController
{
    use ResponseTrait;

    /** What the form offers. Anything else is somebody posting by hand. */
    private const ROOM_TYPES = ['deluxe', 'standard', 'either'];

    public function submit()
    {
        // Honeypot: bots fill the hidden field. Pretend success, as the contact
        // endpoint does — telling them they were caught only helps them.
        if (trim((string) $this->request->getVar('website')) !== '') {
            return $this->respond(['status' => 'success']);
        }

        $rules = [
            'name'      => 'required|min_length[2]|max_length[128]',
            'email'     => 'required|valid_email|max_length[191]',
            'phone'     => 'permit_empty|max_length[32]',
            'room_type' => 'required|in_list[' . implode(',', self::ROOM_TYPES) . ']',
            'check_in'  => 'required|valid_date[Y-m-d]',
            'check_out' => 'required|valid_date[Y-m-d]',
            'adults'    => 'required|is_natural_no_zero|less_than_equal_to[20]',
            'children'  => 'permit_empty|is_natural|less_than_equal_to[20]',
            'message'   => 'permit_empty|max_length[2000]',
        ];

        if (! $this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        $checkIn  = $this->request->getVar('check_in');
        $checkOut = $this->request->getVar('check_out');

        // Compared as strings on purpose: both are already validated Y-m-d, and
        // that format sorts lexicographically, so this needs no timezone.
        if ($checkOut <= $checkIn) {
            return $this->failValidationErrors(['check_out' => lang('Site.booking.err_order')]);
        }
        if ($checkIn < date('Y-m-d')) {
            return $this->failValidationErrors(['check_in' => lang('Site.booking.err_past')]);
        }

        $children = $this->request->getVar('children');

        (new ContactModel())->insert([
            'name'      => $this->request->getVar('name'),
            'email'     => $this->request->getVar('email'),
            'phone'     => $this->request->getVar('phone'),
            'subject'   => 'Room request — ' . $checkIn . ' to ' . $checkOut,
            'message'   => (string) $this->request->getVar('message'),
            'room_type' => $this->request->getVar('room_type'),
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
            'adults'    => (int) $this->request->getVar('adults'),
            'children'  => ($children === null || $children === '') ? 0 : (int) $children,
            'locale'    => substr((string) $this->request->getVar('locale'), 0, 5) ?: 'en',
            'source'    => 'booking_form',
            'status'    => 'new',
        ]);

        return $this->respondCreated(['status' => 'success']);
    }
}
