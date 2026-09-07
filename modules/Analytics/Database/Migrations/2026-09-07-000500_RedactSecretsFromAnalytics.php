<?php

namespace Modules\Analytics\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Remove the credentials that were written into `analytics.path`.
 *
 * `TrackPageView` records every GET that returns 200 text/html, and a
 * password-reset link is exactly that — so the live reset token went into the
 * table in plaintext. Not a token-shaped string: sha256 of it matches
 * `users.reset_token`, so the analytics table held a working password reset for
 * every account that had asked for one. `LearnerAuth::issueToken()` stores only
 * the hash so that a copy of the database is not a reset for everybody, and
 * this handed the plaintext back to the same database.
 *
 * Certificate verification codes were recorded the same way.
 *
 * The filter no longer records them, but stopping the leak does not undo it:
 * the rows are kept for the 400-day analytics retention and are in every backup
 * taken since the site went up. So the historical rows are rewritten to the
 * route shape the filter now records, which keeps the page-view counts intact
 * and destroys the secret.
 *
 * Rewritten rather than deleted, because deleting would silently change
 * historical traffic figures and somebody would eventually notice the gap and
 * wonder what else had been removed.
 */
class RedactSecretsFromAnalytics extends Migration
{
    /** Shapes whose trailing segment is a secret. Mirrors TrackPageView::REDACT. */
    private const PATTERNS = [
        'account/reset',
        'account/verify',
        'verify',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('analytics')) {
            return;
        }

        $redacted = 0;

        foreach (self::PATTERNS as $shape) {
            // Both the bare and the locale-prefixed forms, matched in SQL so a
            // table with a year of traffic in it is not read into PHP.
            foreach (['/' . $shape . '/%', '/__/' . $shape . '/%'] as $like) {
                $rows = $this->db->table('analytics')
                    ->select('id, path')
                    ->like('path', $like, 'none', null, true)
                    ->get()->getResultArray();

                foreach ($rows as $row) {
                    $locale = preg_match('#^/([a-z]{2})/#i', (string) $row['path'], $m) === 1 ? '/' . $m[1] : '';

                    $this->db->table('analytics')
                        ->where('id', (int) $row['id'])
                        ->update(['path' => $locale . '/' . $shape]);

                    $redacted++;
                }
            }
        }

        if ($redacted > 0) {
            log_message('notice', 'Redacted {n} analytics row(s) that contained a credential in the path.', ['n' => $redacted]);
        }
    }

    public function down(): void
    {
        // Deliberately irreversible. The whole point was to destroy the
        // secrets; keeping a way back would mean keeping them.
    }
}
