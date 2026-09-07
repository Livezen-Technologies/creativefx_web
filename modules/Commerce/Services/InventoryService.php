<?php

namespace Modules\Commerce\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Seat inventory.
 *
 * This is the one piece of this site that cannot be allowed to be *nearly*
 * right. Selling the same classroom seat twice is not a display bug: two people
 * arrive in Colombo on a Tuesday morning for a class with twelve chairs and
 * thirteen names, and no amount of apologising afterwards buys back the
 * corporate account that sent them. Everywhere else in this codebase a read
 * followed by a write is fine. Here it is the bug.
 *
 * The existing platform has no transactions at all — the Hantana booking form
 * it grew from counts seats, then inserts, and lives with the race because a
 * government training centre has a waitlist and no money changes hands. Paid
 * seats do not have that luxury, so this class is the first transactional code
 * in the codebase and deliberately does not follow the read-then-write habit
 * around it.
 *
 * ── How a seat is held ──────────────────────────────────────────────────────
 *
 *   1. Open a transaction and **take the write lock on the session row before
 *      reading it**. On MySQL that is `SELECT … FOR UPDATE`. SQLite has no row
 *      locks, but it has something stronger — one writer for the whole database
 *      — which it only takes when a write is actually issued, so a plain
 *      `BEGIN` followed by a `SELECT` gives no protection at all there. The
 *      SQLite path therefore issues a no-op `UPDATE` on the row first,
 *      specifically to escalate the transaction to a write lock. Getting this
 *      backwards is the trap: the code would look transactional, pass every
 *      single-threaded test, and oversell under load.
 *
 *   2. Compute what is actually free:
 *
 *          left = seats_total − seats_sold − Σ(unexpired holds by other carts)
 *
 *      Expiry is read as `expires_at > now` at every point availability is
 *      computed, not taken on trust from the sweeper having run. A late cron
 *      must never be able to oversell; at worst it leaves a seat looking busy
 *      for a few extra minutes, which is the safe direction to be wrong in.
 *
 *   3. Insert or extend this cart's hold, keep `seats_reserved` in step in the
 *      same transaction, and commit.
 *
 * `seats_reserved` is a cache, not the truth. `seat_holds` is the truth. The
 * column exists so a catalogue listing can print "2 seats left" without a
 * correlated subquery per row; `reconcile()` recomputes it, and
 * `spark seats:reconcile` is there for when somebody edits the database by
 * hand, which somebody always does.
 *
 * A session with `seats_total` of 0 is unlimited — self-paced study, and the
 * occasional webinar — and takes none of this machinery.
 */
class InventoryService
{
    /** How long a cart keeps its seats. Long enough to type four attendees in. */
    public const HOLD_MINUTES = 15;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();

        // SQLite serialises writers, so a second process arriving mid-hold gets
        // SQLITE_BUSY rather than waiting. Five seconds of patience turns a
        // spurious failure under local concurrency into the queue it should be.
        // Harmless on MySQL, which ignores the pragma.
        if ($this->isSqlite()) {
            $this->db->query('PRAGMA busy_timeout = 5000');
        }
    }

    // ── Reading, without locking ────────────────────────────────────────────

    /**
     * Seats left on a session, or null when it is unlimited.
     *
     * Safe to call from a listing: it does not lock and does not pretend the
     * answer will still be true a second later. The booking path never trusts
     * it — it re-derives the same number under a lock — because a number shown
     * on a page and a number a purchase is authorised against are different
     * things with different guarantees.
     *
     * @param int|null $exceptCart a cart whose own hold should not count against it
     */
    public function seatsLeft(int $sessionId, ?int $exceptCart = null): ?int
    {
        $session = $this->db->table('course_sessions')
            ->select('seats_total, seats_sold')
            ->where('id', $sessionId)
            ->get()->getRowArray();

        if ($session === null) {
            return 0;
        }

        return $this->computeLeft(
            (int) $session['seats_total'],
            (int) $session['seats_sold'],
            $this->heldBy($sessionId, $exceptCart)
        );
    }

    /**
     * Seats left for many sessions at once, keyed by session id.
     *
     * The schedule page lists eighty dates; asking per row is eighty queries
     * and the reason listing pages quietly become slow.
     *
     * @param list<int> $sessionIds
     * @return array<int, int|null>
     */
    public function seatsLeftFor(array $sessionIds, ?int $exceptCart = null): array
    {
        $sessionIds = array_values(array_unique(array_map('intval', $sessionIds)));
        if ($sessionIds === []) {
            return [];
        }

        $rows = $this->db->table('course_sessions')
            ->select('id, seats_total, seats_sold')
            ->whereIn('id', $sessionIds)
            ->get()->getResultArray();

        $held = $this->db->table('seat_holds')
            ->select('session_id, SUM(qty) AS held', false)
            ->whereIn('session_id', $sessionIds)
            ->where('expires_at >', date('Y-m-d H:i:s'));
        if ($exceptCart !== null) {
            $held->where('cart_id !=', $exceptCart);
        }
        $heldBy = array_column($held->groupBy('session_id')->get()->getResultArray(), 'held', 'session_id');

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['id']] = $this->computeLeft(
                (int) $row['seats_total'],
                (int) $row['seats_sold'],
                (int) ($heldBy[$row['id']] ?? 0)
            );
        }

        return $out;
    }

    // ── Writing, under a lock ───────────────────────────────────────────────

    /**
     * Hold `qty` seats on a session for a cart, replacing whatever that cart
     * already held there.
     *
     * Returns the outcome rather than throwing, because "the last seat went
     * while you were reading the page" is an ordinary thing that happens to
     * honest buyers and the checkout has to say so kindly.
     *
     * @return array{ok:bool, reason:string, left:int|null, held:int}
     *         reason ∈ ok | gone | closed | missing | short
     */
    public function hold(int $sessionId, int $qty, int $cartId): array
    {
        $qty = max(1, $qty);

        $this->db->transStart();

        $session = $this->lockSession($sessionId);
        if ($session === null) {
            $this->db->transComplete();

            return ['ok' => false, 'reason' => 'missing', 'left' => 0, 'held' => 0];
        }

        // A draft, cancelled or completed session is not for sale, whatever its
        // seat count says. Checked inside the lock with everything else so a
        // session being closed by an administrator at that moment cannot be
        // sold in the gap.
        if (! in_array($session['status'], ['open', 'confirmed', 'waitlist'], true)) {
            $this->db->transComplete();

            return ['ok' => false, 'reason' => 'closed', 'left' => 0, 'held' => 0];
        }

        $unlimited = (int) $session['seats_total'] === 0;
        $left      = $unlimited
            ? null
            : $this->computeLeft(
                (int) $session['seats_total'],
                (int) $session['seats_sold'],
                $this->heldBy($sessionId, $cartId)
            );

        if (! $unlimited && $left < $qty) {
            $this->db->transComplete();

            return [
                'ok'     => false,
                'reason' => $left === 0 ? 'gone' : 'short',
                'left'   => $left,
                'held'   => 0,
            ];
        }

        $expires = date('Y-m-d H:i:s', time() + self::HOLD_MINUTES * 60);
        $now     = date('Y-m-d H:i:s');

        $existing = $this->db->table('seat_holds')
            ->where('session_id', $sessionId)->where('cart_id', $cartId)
            ->get()->getRowArray();

        if ($existing !== null) {
            $this->db->table('seat_holds')
                ->where('id', $existing['id'])
                ->update(['qty' => $qty, 'expires_at' => $expires]);
        } else {
            $this->db->table('seat_holds')->insert([
                'session_id' => $sessionId,
                'cart_id'    => $cartId,
                'qty'        => $qty,
                'expires_at' => $expires,
                'created_at' => $now,
            ]);
        }

        $this->refreshReserved($sessionId);
        $this->db->transComplete();

        return [
            'ok'     => $this->db->transStatus() !== false,
            'reason' => 'ok',
            'left'   => $unlimited ? null : $left - $qty,
            'held'   => $qty,
        ];
    }

    /**
     * Give back what a cart was holding — one session's worth, or all of it.
     *
     * Called when an item leaves the cart, when the cart is abandoned, and
     * after an order is paid, since by then the seats are sold rather than
     * held and leaving the hold behind would make the class look fuller than
     * it is.
     */
    public function release(int $cartId, ?int $sessionId = null): void
    {
        $this->db->transStart();

        // Which sessions this touches has to be read before the delete, since
        // afterwards there is nothing left to tell us whose count to refresh.
        $affected = array_column(
            $this->db->table('seat_holds')
                ->select('session_id')
                ->where('cart_id', $cartId)
                ->when($sessionId !== null, static fn ($q) => $q->where('session_id', $sessionId))
                ->get()->getResultArray(),
            'session_id'
        );

        $this->db->table('seat_holds')
            ->where('cart_id', $cartId)
            ->when($sessionId !== null, static fn ($q) => $q->where('session_id', $sessionId))
            ->delete();

        foreach (array_unique($affected) as $id) {
            $this->refreshReserved((int) $id);
        }

        $this->db->transComplete();
    }

    /**
     * Turn a cart's holds into sold seats, once payment is confirmed.
     *
     * The caller supplies the order's items and is expected to be inside its
     * own transaction that also flips the order to paid — the two facts must
     * become true together or a webhook retry sells the seat twice. This
     * method therefore joins the caller's transaction rather than opening its
     * own; CodeIgniter's transaction counter makes nesting safe.
     *
     * Returns the sessions whose counts changed, so the caller can decide
     * whether any of them just reached `min_to_run` and should be confirmed.
     *
     * @param list<array{session_id:int|null, qty:int}> $items
     * @return list<int>
     */
    public function commit(array $items, int $cartId): array
    {
        $touched = [];

        foreach ($items as $item) {
            $sessionId = (int) ($item['session_id'] ?? 0);
            $qty       = max(1, (int) ($item['qty'] ?? 1));
            if ($sessionId === 0) {
                continue;
            }

            $this->lockSession($sessionId);

            // Deliberately not "seats_sold = seats_sold + qty" computed in PHP:
            // the increment is done by the database so that two commits landing
            // together cannot both read the same starting value.
            $this->db->query(
                'UPDATE course_sessions SET seats_sold = seats_sold + ? WHERE id = ?',
                [$qty, $sessionId]
            );

            $this->db->table('seat_holds')
                ->where('session_id', $sessionId)->where('cart_id', $cartId)
                ->delete();

            $this->refreshReserved($sessionId);
            $touched[] = $sessionId;
        }

        return array_values(array_unique($touched));
    }

    /**
     * Take a sold seat directly, with no cart and no hold in between.
     *
     * `commit()` is the checkout's path: it converts holds a cart already owns.
     * A transfer has no cart — an administrator is moving somebody who has
     * already paid from one date to another — so the seat has to be taken in
     * one operation, under the same lock, refusing if the destination is full.
     *
     * Without this the move would have to be spelled out in the controller as a
     * read of `seats_sold` and then a write, which is the exact race the lock
     * exists to prevent: two administrators moving two learners onto the last
     * seat of the same class both read "one left".
     *
     * @return array{ok:bool, reason:string, left:int|null}
     */
    public function sellDirect(int $sessionId, int $qty = 1): array
    {
        $qty = max(1, $qty);

        $this->db->transStart();

        $session = $this->lockSession($sessionId);
        if ($session === null) {
            $this->db->transComplete();

            return ['ok' => false, 'reason' => 'missing', 'left' => 0];
        }

        if (! in_array($session['status'], ['open', 'confirmed', 'waitlist'], true)) {
            $this->db->transComplete();

            return ['ok' => false, 'reason' => 'closed', 'left' => 0];
        }

        $unlimited = (int) $session['seats_total'] === 0;
        $left      = $unlimited
            ? null
            : $this->computeLeft(
                (int) $session['seats_total'],
                (int) $session['seats_sold'],
                $this->heldBy($sessionId, 0)
            );

        if (! $unlimited && $left < $qty) {
            $this->db->transComplete();

            return ['ok' => false, 'reason' => $left === 0 ? 'gone' : 'short', 'left' => $left];
        }

        $this->db->query(
            'UPDATE course_sessions SET seats_sold = seats_sold + ? WHERE id = ?',
            [$qty, $sessionId]
        );

        $this->db->transComplete();

        return ['ok' => true, 'reason' => 'ok', 'left' => $unlimited ? null : $left - $qty];
    }

    /**
     * Give a sold seat back — a refund, a cancellation, a transfer away.
     */
    public function unsell(int $sessionId, int $qty = 1): void
    {
        $this->db->transStart();
        $this->lockSession($sessionId);
        // Clamped at zero: a double refund must not drive the count negative
        // and make a full class look bookable.
        $this->db->query(
            'UPDATE course_sessions SET seats_sold = CASE WHEN seats_sold < ? THEN 0 ELSE seats_sold - ? END WHERE id = ?',
            [$qty, $qty, $sessionId]
        );
        $this->db->transComplete();
    }

    // ── Housekeeping ────────────────────────────────────────────────────────

    /**
     * Delete holds that have run out. Run from the scheduler.
     *
     * Nothing depends on this having run — availability already ignores expired
     * rows — so it is a tidy-up, not a correctness step. It matters only
     * because `seats_reserved` would otherwise stay high and a class would look
     * fuller than it is.
     */
    public function releaseExpired(): int
    {
        $now  = date('Y-m-d H:i:s');
        $rows = $this->db->table('seat_holds')
            ->select('id, session_id')
            ->where('expires_at <=', $now)
            ->get()->getResultArray();

        if ($rows === []) {
            return 0;
        }

        $this->db->transStart();
        $this->db->table('seat_holds')->whereIn('id', array_column($rows, 'id'))->delete();
        foreach (array_unique(array_column($rows, 'session_id')) as $sessionId) {
            $this->refreshReserved((int) $sessionId);
        }
        $this->db->transComplete();

        return count($rows);
    }

    /**
     * Recompute `seats_reserved` from the holds that actually exist, and
     * `seats_sold` from the order items that were actually paid for.
     *
     * The cache should never be wrong. It will be, one day, because somebody
     * will fix something with an UPDATE at two in the morning. Returns the
     * number of sessions it had to correct, so the command can say whether it
     * found anything rather than always printing "done".
     */
    public function reconcile(?int $sessionId = null): int
    {
        $sessions = $this->db->table('course_sessions')->select('id, seats_reserved, seats_sold')
            ->when($sessionId !== null, static fn ($q) => $q->where('id', $sessionId))
            ->get()->getResultArray();

        $fixed = 0;
        foreach ($sessions as $session) {
            $id       = (int) $session['id'];
            $reserved = $this->heldBy($id, null);

            $sold = (int) ($this->db->table('order_items oi')
                ->select('COALESCE(SUM(oi.qty), 0) AS sold', false)
                ->join('orders o', 'o.id = oi.order_id')
                ->where('oi.session_id', $id)
                ->whereIn('o.status', ['paid', 'partially_refunded'])
                ->get()->getRowArray()['sold'] ?? 0);

            if ($reserved !== (int) $session['seats_reserved'] || $sold !== (int) $session['seats_sold']) {
                $this->db->table('course_sessions')->where('id', $id)
                    ->update(['seats_reserved' => $reserved, 'seats_sold' => $sold]);
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Move a session to `confirmed` once it has reached the number of seats it
     * needs to run, and to `full` when there is nothing left.
     *
     * "This class is confirmed to run" removes the single biggest hesitation in
     * booking a dated course, so it is worth being automatic rather than
     * something an administrator remembers.
     */
    public function refreshStatus(int $sessionId): string
    {
        $session = $this->db->table('course_sessions')->where('id', $sessionId)->get()->getRowArray();
        if ($session === null) {
            return '';
        }
        // Only the two statuses that describe an on-sale class are managed here.
        // A cancelled or completed session is a decision somebody made, and this
        // must not undo it.
        if (! in_array($session['status'], ['open', 'confirmed', 'full'], true)) {
            return (string) $session['status'];
        }

        $total = (int) $session['seats_total'];
        $sold  = (int) $session['seats_sold'];
        $left  = $total === 0 ? null : $this->computeLeft($total, $sold, $this->heldBy($sessionId, null));

        $status = $left !== null && $left <= 0
            ? 'full'
            : ($sold >= max(1, (int) $session['min_to_run']) ? 'confirmed' : 'open');

        if ($status !== $session['status']) {
            $this->db->table('course_sessions')->where('id', $sessionId)->update(['status' => $status]);
        }

        return $status;
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Take the write lock on a session row and return it.
     *
     * The whole correctness of this class is in these six lines. See the class
     * comment for why the two drivers need different statements and why a plain
     * SELECT inside a transaction is not a lock on SQLite.
     */
    private function lockSession(int $sessionId): ?array
    {
        if ($this->isSqlite()) {
            // A write, issued only to escalate this transaction to SQLite's
            // single-writer lock before anything is read. `updated_at =
            // updated_at` changes nothing and is not visible to anybody.
            $this->db->query('UPDATE course_sessions SET updated_at = updated_at WHERE id = ?', [$sessionId]);

            return $this->db->query('SELECT * FROM course_sessions WHERE id = ?', [$sessionId])->getRowArray();
        }

        return $this->db
            ->query('SELECT * FROM course_sessions WHERE id = ? FOR UPDATE', [$sessionId])
            ->getRowArray();
    }

    /** Unexpired seats held on a session, optionally ignoring one cart's own. */
    private function heldBy(int $sessionId, ?int $exceptCart): int
    {
        $builder = $this->db->table('seat_holds')
            ->select('COALESCE(SUM(qty), 0) AS held', false)
            ->where('session_id', $sessionId)
            ->where('expires_at >', date('Y-m-d H:i:s'));

        if ($exceptCart !== null) {
            $builder->where('cart_id !=', $exceptCart);
        }

        return (int) ($builder->get()->getRowArray()['held'] ?? 0);
    }

    private function refreshReserved(int $sessionId): void
    {
        $this->db->table('course_sessions')->where('id', $sessionId)
            ->update(['seats_reserved' => $this->heldBy($sessionId, null)]);
    }

    private function computeLeft(int $total, int $sold, int $held): ?int
    {
        if ($total === 0) {
            return null;
        }

        return max(0, $total - $sold - $held);
    }

    private function isSqlite(): bool
    {
        return stripos($this->db->DBDriver, 'sqlite') !== false;
    }
}
