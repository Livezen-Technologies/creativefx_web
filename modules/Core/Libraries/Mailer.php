<?php

namespace Modules\Core\Libraries;

use Config\Services;

/**
 * Sending mail with the settings an editor entered, not the ones in a config file.
 *
 * Nothing about the hotel's mail server belongs in the repository: the host and
 * the account change when the hotel changes provider, and the password is not
 * ours to hold in version control. All of it comes from the settings table, and
 * the password is decrypted only at the moment of sending.
 *
 * Two decisions worth stating:
 *
 *   - Unconfigured means "do not send", not "try and fail". Until a host is set
 *     the site quietly does not email — enquiries are still saved and still
 *     visible in the console, which is the part that must never depend on a
 *     mail server being reachable.
 *   - A failure returns the reason. CodeIgniter's own debugger produces a wall
 *     of protocol chatter; the interesting line is nearly always the last one
 *     the server said, so that is what is surfaced, with the rest kept for
 *     anyone who needs it.
 */
final class Mailer
{
    public static function isConfigured(): bool
    {
        return trim((string) setting('host', '', 'smtp')) !== '';
    }

    /**
     * @param list<array{name:string, content:string, mime:string}> $attachments
     *        Files built in memory rather than read from disk — a calendar
     *        invitation for a booked class, an invoice. Last argument, so every
     *        existing caller is untouched.
     *
     * @return array{sent:bool, error:string, detail:string}
     */
    public static function send(string $to, string $subject, string $body, ?string $replyTo = null, array $attachments = []): array
    {
        if (! self::isConfigured()) {
            return ['sent' => false, 'error' => 'No SMTP host is configured, so nothing was sent.', 'detail' => ''];
        }

        $to = trim($to);
        if ($to === '') {
            return ['sent' => false, 'error' => 'No recipient address is configured.', 'detail' => ''];
        }

        $fromEmail = trim((string) setting('from_email', '', 'smtp'))
            ?: trim((string) setting('email', '', 'contact'));
        if ($fromEmail === '') {
            return ['sent' => false, 'error' => 'No sender address is configured.', 'detail' => ''];
        }

        try {
            $email = Services::email(null, false);
            $email->initialize([
                'protocol'   => 'smtp',
                'SMTPHost'   => trim((string) setting('host', '', 'smtp')),
                'SMTPUser'   => trim((string) setting('user', '', 'smtp')),
                'SMTPPass'   => SecretBox::decrypt((string) setting('pass', '', 'smtp')),
                'SMTPPort'   => (int) (setting('port', '587', 'smtp') ?: 587),
                'SMTPCrypto' => (string) setting('crypto', 'tls', 'smtp'),
                'SMTPTimeout' => 15,
                'mailType'   => 'html',
                'charset'    => 'UTF-8',
                'newline'    => "\r\n",
                'CRLF'       => "\r\n",
            ]);

            $email->setFrom($fromEmail, trim((string) setting('from_name', '', 'smtp')) ?: (string) setting('site_name', ''));
            $email->setTo(array_map('trim', explode(',', $to)));
            if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $email->setReplyTo($replyTo);
            }
            $email->setSubject($subject);
            $email->setMessage($body);

            foreach ($attachments as $attachment) {
                // CodeIgniter's attach() takes a path *or* a buffer, and the
                // only thing that distinguishes them is whether a mime type was
                // given: with one, the first argument is treated as content.
                // These files are built in memory — a calendar invitation, an
                // invoice — and writing each to a temporary file first would
                // leave a learner's details on the disk for whoever finds them.
                // A blank mime would silently make it look for a file named
                // after the entire PDF.
                $email->attach(
                    $attachment['content'],
                    'attachment',
                    $attachment['name'] ?? 'attachment',
                    $attachment['mime'] ?? 'application/octet-stream'
                );
            }

            if ($email->send(false)) {
                return ['sent' => true, 'error' => '', 'detail' => ''];
            }

            $detail = (string) $email->printDebugger(['headers', 'subject', 'body']);

            return ['sent' => false, 'error' => self::readableError($detail), 'detail' => $detail];
        } catch (\Throwable $e) {
            return ['sent' => false, 'error' => $e->getMessage(), 'detail' => ''];
        }
    }

    /**
     * The part of a debugger dump that says what actually went wrong.
     *
     * printDebugger() returns the failure sentence followed by the whole
     * message — headers, subject, body — with no reliable newline between them,
     * so taking "the first line" hands the editor a paragraph of RFC 5322. The
     * text is cut at the first header-looking token, which is where the
     * explanation ends and the transcript begins.
     */
    private static function readableError(string $debug): string
    {
        $text = trim(strip_tags($debug));
        if ($text === '') {
            return 'The mail server refused the message and gave no reason.';
        }

        // An SMTP reply code is the most precise answer available; prefer one.
        foreach (preg_split('/\R/', $text) ?: [] as $line) {
            if (preg_match('/^\s*([45]\d\d[ -].+)$/', $line, $m) === 1) {
                return mb_substr(trim($m[1]), 0, 200);
            }
        }

        $first = trim((string) (preg_split('/\R/', $text)[0] ?? $text));
        $first = (string) preg_split('/(?:Date|From|To|Subject|Return-Path|User-Agent|X-\w+|MIME-Version|Content-Type):/', $first)[0];

        return mb_substr(trim($first), 0, 200)
            ?: 'The mail server refused the message and gave no reason.';
    }

    /** Who should hear about a booking request, falling back sensibly. */
    public static function bookingRecipients(): string
    {
        return trim((string) setting('booking_to', '', 'smtp'))
            ?: trim((string) setting('email', '', 'contact'));
    }

    /** Who should hear about a message. */
    public static function contactRecipients(): string
    {
        return trim((string) setting('contact_to', '', 'smtp')) ?: self::bookingRecipients();
    }
}
