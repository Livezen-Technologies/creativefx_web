<?php

namespace Modules\Learning\Libraries;

use Modules\Core\Libraries\Mailer;

/**
 * What lands in an inbox when a booking is confirmed.
 *
 * Two different letters, because there are two different people. The **buyer**
 * gets a receipt: what was bought, for how much, and the invoice. Each
 * **attendee** gets joining instructions: where to be, when, in their own
 * timezone as well as the class's, what to prepare, and a calendar file. On a
 * corporate order those are different addresses, and sending the finance
 * manager the joining link while the learner gets nothing is the single most
 * common way a training booking goes wrong.
 *
 * Nothing here can stop a purchase. The money has been taken and the seat is
 * theirs; a mail server that is down is a resend in the admin, not an error
 * page.
 */
class EnrolmentMail
{
    /**
     * @param list<array{enrolment_id:int, attendee:array, item:array}> $notify
     */
    public static function send(array $order, array $notify): void
    {
        if (! Mailer::isConfigured()) {
            // Deliberately quiet, and deliberately logged. Until SMTP is set up
            // the site takes bookings and records them; it just cannot tell
            // anybody yet, and the admin shows the enrolment either way.
            log_message('info', 'Order {no} fulfilled but no SMTP is configured, so nothing was emailed.', [
                'no' => $order['order_no'],
            ]);

            return;
        }

        helper(['norlanka', 'url']);

        $db      = db_connect();
        $billing = json_decode((string) $order['billing_json'], true) ?: [];
        $school  = (string) setting('site_name', 'MyLearnPlus');
        $from    = trim((string) setting('from_email', '', 'smtp')) ?: (string) setting('email', '', 'contact');

        foreach ($notify as $entry) {
            $item      = $entry['item'];
            $attendee  = $entry['attendee'];
            $session   = empty($item['session_id']) ? null
                : $db->table('course_sessions')->where('id', (int) $item['session_id'])->get()->getRowArray();
            $course    = empty($item['course_id']) ? null
                : $db->table('courses')->where('id', (int) $item['course_id'])->get()->getRowArray();
            $venue     = $session && $session['venue_id']
                ? $db->table('venues')->where('id', (int) $session['venue_id'])->get()->getRowArray()
                : null;

            $title = $course !== null ? t_field($course['title']) : (string) $item['title_snapshot'];

            $body = view('Modules\Learning\Views\emails\joining', [
                'attendee' => $attendee,
                'order'    => $order,
                'item'     => $item,
                'session'  => $session,
                'course'   => $course,
                'venue'    => $venue,
                'title'    => $title,
                'school'   => $school,
            ], ['saveData' => false]);

            $attachments = [];
            if ($session !== null && ! empty($session['start_date'])) {
                $days = $db->table('session_days')->where('session_id', (int) $session['id'])
                    ->orderBy('day_date', 'ASC')->get()->getResultArray();

                $events = [];
                foreach ($days ?: [['day_date' => $session['start_date'], 'start_time' => $session['daily_start'], 'end_time' => $session['daily_end']]] as $i => $day) {
                    $events[] = [
                        'uid'         => sprintf('mlp-%d-%d-%d@%s', (int) $session['id'], (int) $entry['enrolment_id'], $i, parse_url(base_url(), PHP_URL_HOST) ?: 'mylearnplus.com'),
                        'summary'     => $title . ' — ' . $school,
                        'description' => strip_tags((string) ($course['summary'] ?? '')),
                        'location'    => $venue !== null ? $venue['name'] : 'Live online',
                        'start'       => $day['day_date'] . ' ' . ($day['start_time'] ?: '09:00:00'),
                        'end'         => $day['day_date'] . ' ' . ($day['end_time'] ?: '16:00:00'),
                        'timezone'    => (string) $session['timezone'],
                        'url'         => rtrim(base_url(), '/') . '/en/account/live/' . (int) $session['id'],
                    ];
                }

                $attachments[] = [
                    'name'    => 'class.ics',
                    'mime'    => 'text/calendar',
                    'content' => IcsBuilder::build($events, $school, $from ?: 'no-reply@mylearnplus.com'),
                ];
            }

            Mailer::send(
                $attendee['email'],
                sprintf('%s — you are booked on %s', $school, $title),
                $body,
                null,
                $attachments
            );
        }

        // The receipt, to whoever paid — unless they are also the only attendee,
        // in which case one email is enough and two look like a mistake.
        $buyerEmail = trim((string) ($billing['email'] ?? ''));
        $attendees  = array_map(static fn ($n) => strtolower($n['attendee']['email']), $notify);
        if ($buyerEmail !== '' && (count($notify) > 1 || ! in_array(strtolower($buyerEmail), $attendees, true))) {
            Mailer::send(
                $buyerEmail,
                sprintf('%s — order %s confirmed', $school, $order['order_no']),
                view('Modules\Learning\Views\emails\receipt', [
                    'order'   => $order,
                    'billing' => $billing,
                    'items'   => (new \Modules\Commerce\Models\OrderModel())->items((int) $order['id']),
                    'school'  => $school,
                ], ['saveData' => false])
            );
        }
    }
}
