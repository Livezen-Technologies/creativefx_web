<?php

namespace Modules\Account\Libraries;

use Modules\Auth\Models\UserModel;

/**
 * The learner's session.
 *
 * Deliberately separate from `admin_user`. They are different populations with
 * different powers, and one session key for both is how a bug in the shop ends
 * up being a way into the console. The Authority portal this platform grew from
 * made the same separation for its field officers, for the same reason.
 *
 * Passwords are checked with a constant-time verify, and a failed attempt costs
 * the same time as a successful one whether or not the email exists — otherwise
 * the login form answers the question "does this person have an account here?"
 * to anybody who asks it, which for a training school is a customer list.
 */
class LearnerAuth
{
    public const SESSION_KEY = 'learner';

    /** Attempts per minute, per email and per address. */
    private const RATE_LIMIT = 8;

    public static function user(): ?array
    {
        $stored = session()->get(self::SESSION_KEY);

        return is_array($stored) ? $stored : null;
    }

    public static function id(): ?int
    {
        $user = self::user();

        return $user === null ? null : (int) $user['id'];
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    /**
     * Sign in.
     *
     * @return array{ok:bool, reason:string, user:?array}
     *         reason ∈ ok | bad_credentials | unverified | suspended | throttled
     */
    public static function attempt(string $email, string $password): array
    {
        $email = strtolower(trim($email));

        // Throttled on the email and on the address, so neither a single
        // account nor a single machine can be used to grind through passwords.
        $throttle = service('throttler');
        if ($throttle->check(md5('login-' . $email), self::RATE_LIMIT, MINUTE) === false
            || $throttle->check(md5('login-ip-' . service('request')->getIPAddress()), self::RATE_LIMIT * 3, MINUTE) === false) {
            return ['ok' => false, 'reason' => 'throttled', 'user' => null];
        }

        $users = new UserModel();
        $user  = $users->findByEmail($email);

        // A dummy verify when the account does not exist, so that "no such
        // email" and "wrong password" take the same time. Without it the form
        // is an oracle for whether somebody has bought a course here.
        if ($user === null) {
            password_verify($password, '$2y$10$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG');

            return ['ok' => false, 'reason' => 'bad_credentials', 'user' => null];
        }

        if (! password_verify($password, (string) $user['password_hash'])) {
            return ['ok' => false, 'reason' => 'bad_credentials', 'user' => null];
        }

        // `invited` is an account created for somebody a colleague booked a
        // seat for. It has no usable password, so this branch is reached only
        // if they have set one, at which point they are a normal learner.
        if (($user['status'] ?? 'active') === 'suspended') {
            return ['ok' => false, 'reason' => 'suspended', 'user' => null];
        }

        // Unverified accounts may sign in. A learner whose employer booked
        // their seat must be able to reach their joining link on the morning of
        // the class without first finding a verification email; the account
        // area shows a banner instead, and verification gates only the things
        // that need it.
        self::login($user);

        return ['ok' => true, 'reason' => 'ok', 'user' => $user];
    }

    public static function login(array $user): void
    {
        // A fresh session id on privilege change, so a session id captured
        // before sign-in cannot be used after it.
        session()->regenerate(true);

        session()->set(self::SESSION_KEY, [
            'id'       => (int) $user['id'],
            'email'    => (string) $user['email'],
            'name'     => self::displayName($user),
            'verified' => ! empty($user['email_verified_at']),
        ]);

        (new UserModel())->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
    }

    public static function logout(): void
    {
        session()->remove(self::SESSION_KEY);
        session()->regenerate(true);
    }

    /** Re-read the stored user, for the pages that need more than the session holds. */
    public static function record(): ?array
    {
        $id = self::id();

        return $id === null ? null : (new UserModel())->find($id);
    }

    public static function displayName(array $user): string
    {
        $name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));

        return $name !== '' ? $name : (string) $user['email'];
    }

    // ── Tokens ──────────────────────────────────────────────────────────────

    /**
     * Issue a single-use token and return the half that goes in the email.
     *
     * Only the hash is stored. A verification or reset link sitting in plain
     * text in the database is a password reset for every account, available to
     * anybody who can read a backup — including the backup that goes to a
     * laptop.
     */
    public static function issueToken(int $userId, string $kind, int $hours = 24): string
    {
        $plain = bin2hex(random_bytes(24));

        (new UserModel())->update($userId, [
            $kind . '_token'      => hash('sha256', $plain),
            $kind . '_expires_at' => date('Y-m-d H:i:s', time() + $hours * 3600),
        ]);

        return $plain;
    }

    /** Redeem a token, or null if it is unknown or out of date. */
    public static function consumeToken(string $plain, string $kind): ?array
    {
        if (! preg_match('/^[a-f0-9]{48}$/', $plain)) {
            return null;
        }

        $users = new UserModel();
        $user  = $users->where($kind . '_token', hash('sha256', $plain))->first();

        if ($user === null) {
            return null;
        }
        if (empty($user[$kind . '_expires_at']) || $user[$kind . '_expires_at'] < date('Y-m-d H:i:s')) {
            return null;
        }

        // Cleared on use. A link that works twice is a link that works after it
        // has been forwarded.
        $users->update((int) $user['id'], [$kind . '_token' => null, $kind . '_expires_at' => null]);

        return $user;
    }
}
