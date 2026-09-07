<?php

namespace Modules\Catalog\Models;

use CodeIgniter\Model;

/**
 * The waitlist — what a full class and an unscheduled course have in common.
 *
 * Both are the same commercial event: somebody wanted to buy and could not.
 * That is a lead, not a dead end, and it is frequently the reason a marginal
 * date gets scheduled at all — twelve names against a course nobody has put in
 * the calendar is the strongest argument there is for putting it there.
 *
 * A row therefore hangs off *either* a session or a course, never necessarily
 * both. `session_id` set means "that date is full, tell me if a seat frees up";
 * `course_id` alone means "no date suits me, tell me when you set one". The
 * table allows both to be null at the column level, but `register()` does not:
 * a row attached to nothing can never be acted on and is just an address
 * sitting in a database.
 *
 * Nothing here writes seat counts. A waitlist entry is not a reservation, holds
 * no inventory, and confers no priority beyond the order it was created in —
 * `InventoryService` owns seats, and it is the only thing that does.
 */
class WaitlistModel extends Model
{
    protected $table      = 'waitlist';
    protected $returnType = 'array';

    // The table records when somebody asked and when they were told, and has no
    // `updated_at` at all: a waitlist row is not edited, it is created and then
    // notified. Blanking the updated field is what stops the framework writing
    // to a column that does not exist.
    protected $useTimestamps = true;
    protected $updatedField  = '';

    protected $allowedFields = [
        'session_id', 'course_id', 'user_id', 'name', 'email', 'note', 'notified_at',
    ];

    /**
     * Put somebody on the list, once.
     *
     * The same person hitting the same button twice — a double-tapped form, a
     * back button, a second visit a week later — must not produce two rows,
     * because every row is one email that will be sent when the date is set and
     * two of them read as a mailing list nobody agreed to.
     *
     * The de-duplication key is the address plus the thing being waited for,
     * matched with the same nullness the row was written with: waiting for
     * session 214 and waiting for the course in general are two different
     * requests from the same person and both are legitimate.
     *
     * This is a check followed by an insert rather than an upsert, because the
     * migration carries no unique index over (email, session_id, course_id) —
     * two requests landing in the same millisecond can still both pass. That is
     * a duplicate email at worst, and the notify path groups by address; a
     * unique index would close it properly and is worth adding.
     *
     * @param array{session_id?:?int, course_id?:?int, user_id?:?int, name?:?string, email:string, note?:?string} $data
     * @return array{ok:bool, duplicate:bool, id:?int}
     */
    public function register(array $data): array
    {
        // Addresses are compared lowercased, so Anne@Example.com and
        // anne@example.com are one person rather than two rows and two emails.
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email === '') {
            return ['ok' => false, 'duplicate' => false, 'id' => null];
        }

        $sessionId = isset($data['session_id']) && $data['session_id'] !== null ? (int) $data['session_id'] : null;
        $courseId  = isset($data['course_id']) && $data['course_id'] !== null ? (int) $data['course_id'] : null;

        if ($sessionId === null && $courseId === null) {
            return ['ok' => false, 'duplicate' => false, 'id' => null];
        }

        $existing = $this->where('email', $email)
            ->where('session_id', $sessionId)
            ->where('course_id', $courseId)
            ->first();

        if ($existing !== null) {
            return ['ok' => true, 'duplicate' => true, 'id' => (int) $existing['id']];
        }

        $id = $this->insert([
            'session_id' => $sessionId,
            'course_id'  => $courseId,
            'user_id'    => isset($data['user_id']) && $data['user_id'] !== null ? (int) $data['user_id'] : null,
            'name'       => self::trimTo($data['name'] ?? null, 128),
            'email'      => $email,
            'note'       => self::trimTo($data['note'] ?? null, 2000),
        ], true);

        return ['ok' => $id !== false, 'duplicate' => false, 'id' => $id === false ? null : (int) $id];
    }

    /**
     * Everybody still waiting on a session or a course, oldest first.
     *
     * Oldest first because that is the order seats are offered in when one
     * frees up, and a waitlist that hands the seat to whoever is checked first
     * is not a waitlist.
     *
     * @return list<array>
     */
    public function pending(?int $sessionId = null, ?int $courseId = null, int $limit = 0): array
    {
        $this->where('notified_at IS NULL');

        if ($sessionId !== null) {
            $this->where('session_id', $sessionId);
        }
        if ($courseId !== null) {
            $this->where('course_id', $courseId);
        }

        return $this->orderBy('created_at', 'ASC')->orderBy('id', 'ASC')->findAll($limit ?: null);
    }

    /**
     * Stamp rows as told.
     *
     * Written after the message has actually gone out rather than before, so a
     * mail failure leaves the row pending and it is tried again — the opposite
     * order silently drops the one person the list existed for.
     *
     * @param list<int> $ids
     */
    public function markNotified(array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return;
        }

        $this->whereIn('id', $ids)->set('notified_at', date('Y-m-d H:i:s'))->update();
    }

    private static function trimTo(?string $value, int $length): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $length);
    }
}
