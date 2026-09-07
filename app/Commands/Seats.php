<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Modules\Commerce\Services\InventoryService;

/**
 * Seat inventory, from the command line.
 *
 * The same three operations the scheduler runs, available by hand — because the
 * moment somebody needs them is the moment something has gone wrong and waiting
 * an hour for the next cron is not an answer.
 *
 *   php spark seats:release      release holds whose fifteen minutes have passed
 *   php spark seats:reconcile    recompute the cached counts from the real rows
 *   php spark seats:show <id>    what one session's numbers actually are
 *
 * `reconcile` is the interesting one. `seats_reserved` and `seats_sold` are
 * caches; `seat_holds` and the paid order items are the truth. They should never
 * disagree, and one day they will, because somebody will fix something with an
 * UPDATE at two in the morning.
 */
class Seats extends BaseCommand
{
    protected $group       = 'Commerce';
    protected $name        = 'seats:manage';
    protected $description = 'Release expired seat holds, reconcile the cached counts, or inspect one session.';
    protected $usage       = 'seats:manage [release|reconcile|show] [sessionId]';

    public function run(array $params)
    {
        $action    = strtolower((string) ($params[0] ?? 'show'));
        $sessionId = isset($params[1]) ? (int) $params[1] : null;
        $inventory = new InventoryService();

        switch ($action) {
            case 'release':
                $n = $inventory->releaseExpired();
                CLI::write($n === 0 ? 'Nothing had expired.' : "{$n} expired hold(s) released.", 'green');

                return;

            case 'reconcile':
                $n = $inventory->reconcile($sessionId);
                CLI::write(
                    $n === 0 ? 'Seat counts were already correct.' : "{$n} session(s) corrected.",
                    $n === 0 ? 'green' : 'yellow'
                );

                return;

            case 'hold':
                // One seat, one line of output. Exists so the concurrency check
                // (scripts/test-seat-inventory.mjs) can run several of these at
                // once as genuinely separate processes: forking inside PHP
                // shares the database handle, which is precisely the thing the
                // test is trying to contend on.
                if ($sessionId === null || ! isset($params[2])) {
                    CLI::error('Usage: php spark seats:manage hold <sessionId> <cartId>');

                    return;
                }
                $result = $inventory->hold($sessionId, 1, (int) $params[2]);
                CLI::write($result['ok'] ? 'ok' : $result['reason']);

                return;

            case 'hold-naive':
                // The bug, on purpose.
                //
                // A read of the seat count followed by a write, with no
                // transaction and no lock — which is what almost every booking
                // system is written as, and what this codebase did everywhere
                // before InventoryService existed. It is here so that
                // scripts/test-seat-inventory.mjs can prove the concurrency
                // check actually bites: a test that has never been watched to
                // fail is a test nobody knows works.
                //
                // Refused outside development, because a command that oversells
                // seats has no business being reachable on a live box.
                if (ENVIRONMENT === 'production') {
                    CLI::error('hold-naive is a test fixture and is disabled in production.');

                    return;
                }
                if ($sessionId === null || ! isset($params[2])) {
                    CLI::error('Usage: php spark seats:manage hold-naive <sessionId> <cartId>');

                    return;
                }
                CLI::write($this->holdNaive($sessionId, (int) $params[2]));

                return;

            case 'show':
                if ($sessionId === null) {
                    CLI::error('Usage: php spark seats:manage show <sessionId>');

                    return;
                }
                $this->show($sessionId, $inventory);

                return;
        }

        CLI::error('Unknown action. Use release, reconcile, show or hold.');
    }

    private function show(int $sessionId, InventoryService $inventory): void
    {
        $db      = db_connect();
        $session = $db->table('course_sessions cs')
            ->select('cs.*, c.slug AS course_slug')
            ->join('courses c', 'c.id = cs.course_id')
            ->where('cs.id', $sessionId)->get()->getRowArray();

        if ($session === null) {
            CLI::error("No session {$sessionId}.");

            return;
        }

        // Both numbers, side by side, because the whole point of looking is to
        // find out whether the cache and the truth agree.
        $held = (int) ($db->table('seat_holds')
            ->select('COALESCE(SUM(qty), 0) AS held', false)
            ->where('session_id', $sessionId)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->get()->getRowArray()['held'] ?? 0);

        $paid = (int) ($db->table('order_items oi')
            ->select('COALESCE(SUM(oi.qty), 0) AS sold', false)
            ->join('orders o', 'o.id = oi.order_id')
            ->where('oi.session_id', $sessionId)
            ->whereIn('o.status', ['paid', 'partially_refunded'])
            ->get()->getRowArray()['sold'] ?? 0);

        CLI::write($session['course_slug'] . '  #' . $sessionId . '  ' . $session['mode'] . '  ' . ($session['start_date'] ?: 'self-paced'));
        CLI::write('  status          ' . $session['status']);
        CLI::write('  seats_total     ' . $session['seats_total']);
        CLI::write('  seats_sold      ' . $session['seats_sold'] . '   (paid order items: ' . $paid . ')',
            (int) $session['seats_sold'] === $paid ? 'green' : 'red');
        CLI::write('  seats_reserved  ' . $session['seats_reserved'] . '   (unexpired holds: ' . $held . ')',
            (int) $session['seats_reserved'] === $held ? 'green' : 'red');
        CLI::write('  available       ' . var_export($inventory->seatsLeft($sessionId), true));
    }

    /**
     * The unlocked version, for the negative test only.
     *
     * Read, decide, write — with a deliberate pause between the read and the
     * write so that eight processes started together reliably interleave. The
     * pause is what makes the race certain rather than occasional; without it
     * the test would pass most of the time on a fast machine and prove nothing.
     */
    private function holdNaive(int $sessionId, int $cartId): string
    {
        $db      = db_connect();
        $session = $db->table('course_sessions')->select('seats_total, seats_sold, seats_reserved')
            ->where('id', $sessionId)->get()->getRowArray();

        if ($session === null) {
            return 'missing';
        }

        $left = (int) $session['seats_total'] - (int) $session['seats_sold'] - (int) $session['seats_reserved'];
        if ($left < 1) {
            return 'gone';
        }

        usleep(120_000);

        $db->table('seat_holds')->insert([
            'session_id' => $sessionId,
            'cart_id'    => $cartId,
            'qty'        => 1,
            'expires_at' => date('Y-m-d H:i:s', time() + 900),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->query('UPDATE course_sessions SET seats_reserved = seats_reserved + 1 WHERE id = ?', [$sessionId]);

        return 'ok';
    }
}
