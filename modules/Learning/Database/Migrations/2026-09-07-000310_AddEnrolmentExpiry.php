<?php

namespace Modules\Learning\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * When a self-paced enrolment runs out.
 *
 * The catalogue sells the on-demand library as "start the moment you buy it,
 * work at your own pace, and keep access for twelve months" — and there was
 * nowhere to record when those twelve months end, so `hasAccess()` granted the
 * library for ever. Nobody is harmed by that, which is exactly why it would
 * have survived to production: the failure is silent, in the buyer's favour,
 * and only shows up as a licence the business cannot price.
 *
 * Nullable, and null means no expiry — which is the right answer for a taught
 * class. A seat on a two-day course in September is not a subscription; the
 * course happens, the certificate is issued, and the record of having attended
 * it should not stop being readable a year later.
 */
class AddEnrolmentExpiry extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('enrolments', [
            'expires_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'completed_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('enrolments', 'expires_at');
    }
}
