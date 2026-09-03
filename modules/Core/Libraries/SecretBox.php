<?php

namespace Modules\Core\Libraries;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use Config\Services;

/**
 * Settings that must not be readable in the database.
 *
 * An SMTP password and a reCAPTCHA secret are credentials for somebody else's
 * system. Stored in plain text they leak with any database copy — a backup on a
 * laptop, a dump attached to a support ticket — and they are not the site's to
 * leak.
 *
 * Two rules that matter more than the encryption itself:
 *
 *   1. With no key configured, this refuses to store the value rather than
 *      storing it in the clear. Silently degrading to plaintext is how a
 *      credential ends up somewhere nobody expected it to be, and the caller
 *      gets a clear failure it can show instead.
 *   2. A stored secret is never sent back to the browser. The form shows
 *      whether one is set, not what it is; leaving the field blank keeps it.
 *      An admin page that renders a password into an input is one that puts it
 *      in every proxy log and browser cache between here and the editor.
 */
final class SecretBox
{
    /** Marks a stored value as encrypted by this class rather than typed by hand. */
    private const PREFIX = 'enc:v1:';

    public static function hasKey(): bool
    {
        return trim((string) (config('Encryption')->key ?? '')) !== '';
    }

    /**
     * @throws \RuntimeException when there is no key to encrypt with
     */
    public static function encrypt(string $plain): string
    {
        if ($plain === '') {
            return '';
        }
        if (! self::hasKey()) {
            throw new \RuntimeException(
                'No encryption key is configured, so this cannot be stored safely. '
                . 'Set encryption.key in the .env file — a deploy generates one automatically.',
            );
        }

        return self::PREFIX . base64_encode(Services::encrypter()->encrypt($plain));
    }

    /** Returns '' when the value cannot be read, which is indistinguishable from unset — deliberately. */
    public static function decrypt(?string $stored): string
    {
        $stored = (string) $stored;

        if ($stored === '') {
            return '';
        }
        if (! str_starts_with($stored, self::PREFIX)) {
            // Written before this existed, or set by hand in the database. Use
            // it, so nothing breaks, but it is not something to keep.
            return $stored;
        }
        if (! self::hasKey()) {
            return '';
        }

        try {
            return (string) Services::encrypter()->decrypt(base64_decode(substr($stored, strlen(self::PREFIX)), true));
        } catch (EncryptionException | \Throwable $e) {
            // Usually a key that has changed since the value was written. An
            // empty string reads as "not configured", which is the truthful
            // answer: what is stored can no longer be used.
            return '';
        }
    }

    /** Is there a secret here at all — without saying what it is. */
    public static function isSet(?string $stored): bool
    {
        return trim((string) $stored) !== '';
    }
}
