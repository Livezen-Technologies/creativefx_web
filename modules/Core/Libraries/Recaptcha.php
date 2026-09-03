<?php

namespace Modules\Core\Libraries;

/**
 * reCAPTCHA v3, which scores a visitor rather than asking them to identify
 * traffic lights.
 *
 * Two judgements are built in, and both are about which mistake costs the hotel
 * more:
 *
 *   1. Not configured means not enforced. With the feature off, or either key
 *      missing, every submission is accepted exactly as it is today. A
 *      half-configured spam filter that silently rejects real guests is worse
 *      than no filter.
 *   2. Unreachable means accepted. If Google cannot be contacted — an outage, a
 *      firewall, a timeout — the submission goes through and the failure is
 *      logged. A hotel that loses a real booking because a third party was down
 *      has lost more than one that receives a spam message.
 *
 * Only an explicit low score is a rejection, and the guest is told plainly
 * rather than shown a validation error against a field they cannot see.
 */
final class Recaptcha
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    public static function siteKey(): string
    {
        return trim((string) setting('site_key', '', 'recaptcha'));
    }

    /** Enabled and usable — both keys present, not just the switch turned on. */
    public static function isActive(): bool
    {
        return setting('enabled', '0', 'recaptcha') === '1'
            && self::siteKey() !== ''
            && SecretBox::decrypt((string) setting('secret_key', '', 'recaptcha')) !== '';
    }

    /** Is it switched on for this particular form? */
    public static function guards(string $form): bool
    {
        return self::isActive() && setting('on_' . $form, '0', 'recaptcha') === '1';
    }

    /**
     * @return array{ok:bool, score:?float, reason:string}
     */
    public static function verify(?string $token, string $expectedAction = ''): array
    {
        if (! self::isActive()) {
            return ['ok' => true, 'score' => null, 'reason' => 'not configured'];
        }

        $token = trim((string) $token);
        if ($token === '') {
            // No token at all usually means the script did not load — an ad
            // blocker, a firewall, or no JavaScript. That is not evidence of a
            // bot, and a guest with a blocker should still be able to book.
            return ['ok' => true, 'score' => null, 'reason' => 'no token'];
        }

        $response = self::ask($token);
        if ($response === null) {
            return ['ok' => true, 'score' => null, 'reason' => 'verification unreachable'];
        }

        if (($response['success'] ?? false) !== true) {
            $codes = implode(', ', (array) ($response['error-codes'] ?? []));
            // A key problem is our fault, not the visitor's, so it is logged and
            // the submission accepted rather than blamed on them.
            log_message('error', 'reCAPTCHA rejected the request: ' . ($codes ?: 'no reason given'));

            return ['ok' => true, 'score' => null, 'reason' => 'verification failed: ' . $codes];
        }

        if ($expectedAction !== '' && isset($response['action']) && $response['action'] !== $expectedAction) {
            // A token minted for another form being replayed here.
            return ['ok' => false, 'score' => (float) ($response['score'] ?? 0), 'reason' => 'action mismatch'];
        }

        $score     = (float) ($response['score'] ?? 0);
        $threshold = (float) (setting('threshold', '0.5', 'recaptcha') ?: 0.5);
        $threshold = min(1.0, max(0.0, $threshold));

        return [
            'ok'     => $score >= $threshold,
            'score'  => $score,
            'reason' => $score >= $threshold ? 'passed' : 'score ' . $score . ' below ' . $threshold,
        ];
    }

    /** Confirms the keys are accepted, without a real token to score. */
    public static function testConnection(): array
    {
        if (self::siteKey() === '') {
            return ['ok' => false, 'message' => 'No site key is set.'];
        }
        if (SecretBox::decrypt((string) setting('secret_key', '', 'recaptcha')) === '') {
            return ['ok' => false, 'message' => 'No secret key is set, or it cannot be decrypted with the current encryption key.'];
        }

        // Deliberately an invalid token: Google answers with a specific code for
        // a bad token and a different one for a bad secret, which is exactly
        // the distinction a test needs to draw.
        $response = self::ask('connection-test');
        if ($response === null) {
            return ['ok' => false, 'message' => 'Could not reach Google to check. The server may not have outbound internet access.'];
        }

        $codes = (array) ($response['error-codes'] ?? []);
        if (in_array('invalid-input-secret', $codes, true)) {
            return ['ok' => false, 'message' => 'Google rejected the secret key. Check it was copied in full, and that it is the secret rather than the site key.'];
        }
        if (in_array('invalid-input-response', $codes, true) || $codes === []) {
            return ['ok' => true, 'message' => 'Google accepted the secret key. Submit a form to see a real score.'];
        }

        return ['ok' => false, 'message' => 'Google answered: ' . implode(', ', $codes)];
    }

    /** @return array<string, mixed>|null null when Google could not be reached */
    private static function ask(string $token): ?array
    {
        try {
            $client = \Config\Services::curlrequest([
                'timeout'     => 6,
                'http_errors' => false,
            ]);

            $response = $client->post(self::VERIFY_URL, [
                'form_params' => [
                    'secret'   => SecretBox::decrypt((string) setting('secret_key', '', 'recaptcha')),
                    'response' => $token,
                    'remoteip' => \Config\Services::request()->getIPAddress(),
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return is_array($body) ? $body : null;
        } catch (\Throwable $e) {
            log_message('error', 'reCAPTCHA could not be reached: ' . $e->getMessage());

            return null;
        }
    }
}
