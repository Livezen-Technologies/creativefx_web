<?php

/**
 * Which enrolment does the player record a learner's work against?
 *
 * Builds the situation that exposed the bug and is otherwise hard to reach: one
 * learner, one course, a taught seat sat in August and a self-paced licence
 * bought in September. Two rows, and something has to choose between them.
 *
 * Run it with:  php scripts/check-enrolment-pick.php
 *
 * It writes to the database and deletes what it wrote, so point it at a
 * development database, not a live one.
 */

define('FCPATH', dirname(__DIR__) . '/public/');
chdir(FCPATH);
require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
define('ENVIRONMENT', 'development');
CodeIgniter\Boot::bootConsole($paths);

use Modules\Learning\Models\EnrolmentModel;
use Modules\Learning\Services\CertificateService;

$db = db_connect();

$courseId = (int) $db->table('courses')->select('id')->get(1)->getRowArray()['id'];

// A finished classroom session on that course, and a self-paced one.
$db->table('course_sessions')->insert([
    'course_id' => $courseId, 'mode' => 'CLASSROOM', 'language' => 'en',
    'start_date' => '2026-08-03', 'end_date' => '2026-08-04', 'timezone' => 'Asia/Colombo',
    'daily_start' => '09:00', 'daily_end' => '16:00',
    'seats_total' => 12, 'seats_reserved' => 0, 'seats_sold' => 0, 'min_to_run' => 1,
    'status' => 'open', 'is_private' => 0,
    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
]);
$taughtSession = (int) $db->insertID();

// Two teaching days, and no attendance marked against either. Without days on
// the session the attendance rule is vacuous by design — the register decides
// nothing when there is no register — so a fixture without them proves nothing.
foreach (['2026-08-03', '2026-08-04'] as $i => $day) {
    $db->table('session_days')->insert([
        'session_id' => $taughtSession, 'day_date' => $day,
        'start_time' => '09:00:00', 'end_time' => '16:00:00', 'sort_order' => $i,
    ]);
}

$db->table('course_sessions')->insert([
    'course_id' => $courseId, 'mode' => 'SELF_PACED', 'language' => 'en',
    'timezone' => 'Asia/Colombo',
    'seats_total' => 0, 'seats_reserved' => 0, 'seats_sold' => 0, 'min_to_run' => 0,
    'status' => 'open', 'is_private' => 0,
    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
]);
$pacedSession = (int) $db->insertID();

$userId = 999001;
$db->table('enrolments')->where('user_id', $userId)->delete();

// The taught seat comes first, so it has the lower id.
$db->table('enrolments')->insert([
    'user_id' => $userId, 'course_id' => $courseId, 'session_id' => $taughtSession,
    'mode' => 'CLASSROOM', 'status' => 'active', 'source' => 'test',
    'enrolled_at' => '2026-08-01 09:00:00', 'expires_at' => null,
    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
]);
$taughtEnrolment = (int) $db->insertID();

$db->table('enrolments')->insert([
    'user_id' => $userId, 'course_id' => $courseId, 'session_id' => $pacedSession,
    'mode' => 'SELF_PACED', 'status' => 'active', 'source' => 'test',
    'enrolled_at' => '2026-09-01 09:00:00', 'expires_at' => '2027-09-01 09:00:00',
    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
]);
$pacedEnrolment = (int) $db->insertID();

$enrolments = new EnrolmentModel();
$certs      = new CertificateService();

$picked = (int) ($enrolments->accessRowFor($userId, $courseId)['id'] ?? 0);

// What the old rule picked: oldest first, expiry ignored.
$old = (int) ($enrolments->where('user_id', $userId)->where('course_id', $courseId)
    ->whereIn('status', ['active', 'completed'])->orderBy('id', 'ASC')->first()['id'] ?? 0);

$fail = 0;
$check = static function (string $what, $got, $want) use (&$fail): void {
    $ok = $got === $want;
    printf("  %-56s %s (got %s, want %s)\n", $what, $ok ? 'ok' : 'FAIL', var_export($got, true), var_export($want, true));
    $fail += $ok ? 0 : 1;
};

echo "taught enrolment = $taughtEnrolment (older)   self-paced enrolment = $pacedEnrolment (newer)\n\n";
$check('the old rule picked the taught seat', $old, $taughtEnrolment);
$check('accessRowFor() picks the self-paced licence', $picked, $pacedEnrolment);
$check('hasAccess() still true', $enrolments->hasAccess($userId, $courseId), true);
$check('certificate verdict on the OLD pick', $certs->eligible($taughtEnrolment)['reason'], 'attendance');
$check('certificate verdict on the NEW pick is not attendance',
    $certs->eligible($picked)['reason'] !== 'attendance', true);

// A learner with only a lapsed self-paced licence must be shut out entirely.
$db->table('enrolments')->where('id', $taughtEnrolment)->delete();
$db->table('enrolments')->where('id', $pacedEnrolment)->update(['expires_at' => '2026-01-01 00:00:00']);
$check('a lapsed licence grants no access', $enrolments->hasAccess($userId, $courseId), false);
$check('and resolves to no enrolment', $enrolments->accessRowFor($userId, $courseId), null);

// Clean up.
$db->table('enrolments')->where('user_id', $userId)->delete();
$db->table('session_days')->where('session_id', $taughtSession)->delete();
$db->table('course_sessions')->whereIn('id', [$taughtSession, $pacedSession])->delete();

echo "\n", $fail === 0 ? "all checks passed\n" : "$fail check(s) FAILED\n";
exit($fail === 0 ? 0 : 1);
