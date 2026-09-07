<?php

namespace Modules\Core\Libraries;

/**
 * The site's scheduled housekeeping.
 *
 * The registry is code and the schedule is data: what a task *does* belongs in
 * version control, while whether it runs and how often is the school's to change
 * without a deploy. Adding a task is a method here plus an entry in TASKS; the
 * row appears on its own the first time the scheduler is asked about it.
 *
 * Locking is the part that matters. A cron line firing every five minutes will
 * start a second copy of a job the first has not finished, which for a job that
 * deletes rows or sends mail is not a slowdown but a bug. A task is claimed by
 * stamping locked_at, and a lock older than an hour is treated as abandoned —
 * a process killed mid-run must not leave its task stuck forever.
 */
final class Scheduler
{
    /** How long before a lock is assumed to belong to a dead process. */
    private const LOCK_TIMEOUT = 3600;

    private const TASKS = [
        'prune_analytics' => [
            'label'       => 'Trim old analytics',
            'description' => 'Deletes page-view rows older than the retention period. Keeps the table small and honours the promise that visits are not kept indefinitely.',
            'frequency'   => 'daily',
        ],
        'prune_logs' => [
            'label'       => 'Trim old log files',
            'description' => 'Removes framework logs older than 30 days.',
            'frequency'   => 'weekly',
        ],
        'ping_sitemap' => [
            'label'       => 'Tell search engines the sitemap changed',
            'description' => 'Pings Google and Bing with the sitemap address. Harmless if it fails — they will find it anyway.',
            'frequency'   => 'daily',
        ],
        'release_seat_holds' => [
            'label'       => 'Release expired seat holds',
            'description' => 'Deletes seat holds whose fifteen minutes have run out and refreshes the reserved count. Nothing depends on this having run — availability already ignores an expired hold — so a late cron shows a class as fuller than it is rather than overselling it.',
            'frequency'   => 'hourly',
        ],
        'reconcile_seats' => [
            'label'       => 'Reconcile seat counts',
            'description' => 'Recomputes seats_reserved from the holds that exist and seats_sold from the order items that were actually paid for. The cached counts should never be wrong; one day they will be, because somebody will fix something with an UPDATE at two in the morning.',
            'frequency'   => 'daily',
        ],
        'confirm_sessions' => [
            'label'       => 'Confirm classes that have reached their minimum',
            'description' => 'Moves an open session to "confirmed to run" once enough seats are sold, and to "full" when there are none left. "This class is confirmed" removes the biggest hesitation in booking a dated course, so it should not wait for somebody to notice.',
            'frequency'   => 'hourly',
        ],
    ];

    private $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /** Every task with its stored state, creating rows for any that are new. */
    public function all(): array
    {
        $rows = [];
        foreach ($this->db->table('scheduled_tasks')->get()->getResultArray() as $r) {
            $rows[$r['key']] = $r;
        }

        $out = [];
        foreach (self::TASKS as $key => $meta) {
            if (! isset($rows[$key])) {
                $this->db->table('scheduled_tasks')->insert([
                    'key'        => $key,
                    'enabled'    => 1,
                    'frequency'  => $meta['frequency'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $rows[$key] = $this->db->table('scheduled_tasks')->where('key', $key)->get()->getRowArray();
            }

            $row            = $rows[$key];
            $row['label']   = $meta['label'];
            $row['about']   = $meta['description'];
            $row['due_at']  = $this->nextRun($row);
            $row['is_due']  = $this->isDue($row);
            $row['running'] = $this->isLocked($row);
            $out[$key]      = $row;
        }

        return $out;
    }

    /** Runs everything that is due. Returns one line per task for the caller to print. */
    public function runDue(): array
    {
        $report = [];
        foreach ($this->all() as $key => $task) {
            if ((int) $task['enabled'] !== 1) {
                $report[$key] = 'disabled';
                continue;
            }
            if (! $task['is_due']) {
                $report[$key] = 'not due (next ' . $task['due_at'] . ')';
                continue;
            }
            $result       = $this->run($key);
            $report[$key] = $result['status'] . ': ' . $result['message'];
        }

        return $report;
    }

    /**
     * Runs one task, whether or not it is due — this is also what the console's
     * "Run now" uses.
     *
     * @return array{status:string, message:string}
     */
    public function run(string $key): array
    {
        if (! isset(self::TASKS[$key])) {
            return ['status' => 'failed', 'message' => 'Unknown task.'];
        }

        $task = $this->db->table('scheduled_tasks')->where('key', $key)->get()->getRowArray();
        if ($task !== null && $this->isLocked($task)) {
            return ['status' => 'skipped', 'message' => 'Already running since ' . $task['locked_at'] . '.'];
        }

        $this->db->table('scheduled_tasks')->where('key', $key)->update(['locked_at' => date('Y-m-d H:i:s')]);

        $started = microtime(true);
        try {
            $message = $this->{'task' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)))}();
            $status  = 'ok';
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            $status  = 'failed';
            log_message('error', 'Scheduled task ' . $key . ' failed: ' . $e->getMessage());
        }

        $this->db->table('scheduled_tasks')->where('key', $key)->update([
            'last_run_at'  => date('Y-m-d H:i:s'),
            'last_status'  => $status,
            'last_message' => mb_substr($message, 0, 1000),
            'last_ms'      => (int) round((microtime(true) - $started) * 1000),
            'locked_at'    => null,
            'runs'         => (int) ($task['runs'] ?? 0) + 1,
            'failures'     => (int) ($task['failures'] ?? 0) + ($status === 'failed' ? 1 : 0),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return ['status' => $status, 'message' => $message];
    }

    public function setEnabled(string $key, bool $enabled): void
    {
        $this->db->table('scheduled_tasks')->where('key', $key)->update([
            'enabled'    => $enabled ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ---- The tasks themselves ----------------------------------------------

    /**
     * Give back the seats of carts that were abandoned.
     *
     * Tidy-up, not correctness: every query that computes availability already
     * ignores a hold whose `expires_at` has passed, precisely so that a cron
     * that is late — or off — cannot oversell a class. What this fixes is the
     * cached `seats_reserved` count, which would otherwise keep a class looking
     * fuller than it is.
     */
    private function taskReleaseSeatHolds(): string
    {
        $released = (new \Modules\Commerce\Services\InventoryService())->releaseExpired();

        return $released . ' expired hold(s) released.';
    }

    /** Recompute the cached seat counts from the rows that are actually there. */
    private function taskReconcileSeats(): string
    {
        $fixed = (new \Modules\Commerce\Services\InventoryService())->reconcile();

        return $fixed === 0
            ? 'Seat counts were already correct.'
            : $fixed . ' session(s) corrected.';
    }

    /**
     * Move classes to "confirmed" and "full" as their numbers change.
     *
     * Only sessions that are still on sale and still ahead: a completed or
     * cancelled class is a decision somebody made, and a housekeeping job must
     * not undo it.
     */
    private function taskConfirmSessions(): string
    {
        $db        = db_connect();
        $inventory = new \Modules\Commerce\Services\InventoryService();

        $sessions = $db->table('course_sessions')
            ->select('id')
            ->whereIn('status', ['open', 'confirmed', 'full'])
            ->groupStart()
                ->where('start_date IS NULL')
                ->orWhere('start_date >=', date('Y-m-d'))
            ->groupEnd()
            ->get()->getResultArray();

        $changed = 0;
        foreach ($sessions as $session) {
            $before = $db->table('course_sessions')->select('status')
                ->where('id', (int) $session['id'])->get()->getRowArray()['status'] ?? '';
            if ($inventory->refreshStatus((int) $session['id']) !== $before) {
                $changed++;
            }
        }

        return $changed === 0
            ? 'No class changed status.'
            : $changed . ' class(es) changed status.';
    }

    private function taskPruneAnalytics(): string
    {
        $days   = max(7, (int) (setting('retention_days', '400', 'analytics') ?: 400));
        $cutoff = date('Y-m-d 00:00:00', strtotime("-{$days} days"));

        $before = (int) $this->db->table('analytics')->countAllResults();
        $this->db->table('analytics')->where('created_at <', $cutoff)->delete();
        $after = (int) $this->db->table('analytics')->countAllResults();

        return ($before - $after) . ' row(s) older than ' . $days . ' days removed; ' . $after . ' kept.';
    }

    private function taskPruneLogs(): string
    {
        $removed = 0;
        $cutoff  = strtotime('-30 days');
        foreach (glob(WRITEPATH . 'logs/log-*.log') ?: [] as $file) {
            if (filemtime($file) < $cutoff && @unlink($file)) {
                $removed++;
            }
        }

        return $removed . ' log file(s) removed.';
    }

    private function taskPingSitemap(): string
    {
        $sitemap = rtrim((string) config('App')->baseURL, '/') . '/sitemap.xml';
        $done    = [];

        foreach ([
            'Google' => 'https://www.google.com/ping?sitemap=',
            'Bing'   => 'https://www.bing.com/ping?sitemap=',
        ] as $name => $endpoint) {
            try {
                $response = \Config\Services::curlrequest(['timeout' => 8, 'http_errors' => false])
                    ->get($endpoint . urlencode($sitemap));
                $done[] = $name . ' ' . $response->getStatusCode();
            } catch (\Throwable $e) {
                // Not a failure worth flagging: search engines re-crawl a
                // sitemap on their own schedule whether or not they are told.
                $done[] = $name . ' unreachable';
            }
        }

        return implode(', ', $done) . '.';
    }

    // ---- Scheduling ---------------------------------------------------------

    private function isDue(array $task): bool
    {
        if ($task['last_run_at'] === null) {
            return true;
        }

        return strtotime($this->nextRun($task)) <= time();
    }

    private function nextRun(array $task): string
    {
        if ($task['last_run_at'] === null) {
            return 'now';
        }

        $step = match ($task['frequency']) {
            'hourly'  => '+1 hour',
            'weekly'  => '+1 week',
            'monthly' => '+1 month',
            default   => '+1 day',
        };

        return date('Y-m-d H:i', strtotime($step, strtotime((string) $task['last_run_at'])));
    }

    private function isLocked(array $task): bool
    {
        if (empty($task['locked_at'])) {
            return false;
        }

        return (time() - strtotime((string) $task['locked_at'])) < self::LOCK_TIMEOUT;
    }
}
