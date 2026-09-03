<?php

namespace Modules\Tshda\Libraries;

/**
 * Reference numbers for anything a member of the public submits.
 *
 * Clause 3.9 J.a.iii and Clause 3.14 both turn on the same idea: a citizen who
 * submits something to the Authority should be given something to quote, and
 * the Authority should be able to find that submission again from what it gave
 * them. A reference is that handle.
 *
 * The shape is PREFIX-YYMM-XXXXXX, e.g. FBK-2609-4KQ8ZP. The year and month
 * make a reference sortable and legible at a glance — an officer can see that
 * FBK-2604 is five months old without looking it up — and the random tail makes
 * it unguessable, which matters because the tracking page looks a submission up
 * by reference alone. Sequential numbering would let anyone read everyone's.
 *
 * The alphabet leaves out I, O, 0 and 1: these are read aloud over a telephone
 * and copied off a screen by hand, and those four are where that goes wrong.
 */
final class Reference
{
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    private const LENGTH   = 6;

    /**
     * A reference that is not already taken.
     *
     * @param callable(string): bool $exists returns true when the candidate is
     *                                       already in use
     */
    public static function generate(string $prefix, callable $exists): string
    {
        $stamp = date('ym');

        // A collision at 32^6 is vanishingly unlikely, but "unlikely" is not
        // "impossible" and the column is unique — an unchecked insert would
        // throw in front of somebody who had just filled in a form.
        for ($attempt = 0; $attempt < 12; $attempt++) {
            $candidate = strtoupper($prefix) . '-' . $stamp . '-' . self::tail();
            if (! $exists($candidate)) {
                return $candidate;
            }
        }

        // Twelve collisions means something is wrong with the assumption, not
        // with luck. Widen the tail rather than loop forever or return a
        // duplicate.
        return strtoupper($prefix) . '-' . $stamp . '-' . self::tail(10);
    }

    private static function tail(int $length = self::LENGTH): string
    {
        $max = strlen(self::ALPHABET) - 1;
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= self::ALPHABET[random_int(0, $max)];
        }

        return $out;
    }

    /**
     * A one-way fingerprint of the submitter's network address.
     *
     * Kept instead of the address itself so that repeated abuse from one source
     * can be recognised without the site holding a record of who visited it.
     * Salted with the application key, so the hashes are useless outside this
     * installation and cannot be compared against a rainbow table of addresses.
     */
    public static function ipHash(?string $ip): string
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return '';
        }

        return hash_hmac('sha256', $ip, (string) (config('Encryption')->key ?: 'tshda'));
    }
}
