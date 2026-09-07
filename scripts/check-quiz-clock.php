<?php

/**
 * Does a timed quiz's clock actually run?
 *
 * The clock is stamped into the session when the lesson page renders, and the
 * question this answers is what happens on the *second* render. If the stamp is
 * rewritten every time, a learner keeps the full allowance by refreshing — or
 * by opening the lesson in a second tab — and a timed assessment is an untimed
 * one with a sentence about minutes printed above it.
 *
 * Nothing in the seed data exercises the player: there are no lessons, no
 * quizzes and no enrolments, so this builds the whole situation, signs a
 * learner in over HTTP against the dev server, and reads the stamp back out of
 * the session file the framework wrote.
 *
 * Start the server first:
 *   PHP_CLI_SERVER_WORKERS=6 php spark serve --port 8080
 *   php scripts/check-quiz-clock.php
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

$base = getenv('BASE_URL') ?: 'http://127.0.0.1:8080';
$now  = date('Y-m-d H:i:s');
$db   = db_connect();

$course = $db->table('courses')->select('id, slug')->get(1)->getRowArray();
if ($course === null) {
    fwrite(STDERR, "no courses in the database\n");
    exit(2);
}
$courseId = (int) $course['id'];

// ── The fixture ─────────────────────────────────────────────────────────────
$db->table('course_modules')->insert([
    'course_id' => $courseId, 'title' => 'Clock check', 'sort_order' => 999,
]);
$moduleId = (int) $db->insertID();

$db->table('lessons')->insert([
    'course_id' => $courseId, 'module_id' => $moduleId, 'slug' => 'clock-check',
    'title' => 'Clock check', 'type' => 'text', 'body' => 'A lesson with a timed quiz.',
    'is_preview' => 0, 'sort_order' => 1, 'status' => 'published',
    'created_at' => $now, 'updated_at' => $now,
]);
$lessonId = (int) $db->insertID();

$db->table('quizzes')->insert([
    'lesson_id' => $lessonId, 'title' => 'Clock check', 'pass_mark' => 50,
    'attempts_allowed' => 0, 'time_limit_min' => 30, 'is_final' => 0,
    'created_at' => $now, 'updated_at' => $now,
]);
$quizId = (int) $db->insertID();

$db->table('quiz_questions')->insert([
    'quiz_id' => $quizId, 'type' => 'single', 'prompt' => 'Is the clock running?',
    'options_json' => json_encode(['Yes', 'No']), 'answer_json' => json_encode([0]),
    'points' => 1, 'sort_order' => 1,
]);
$questionId = (int) $db->insertID();

$email    = 'clock-check@example.invalid';
$password = 'correct horse battery staple';
$db->table('users')->where('email', $email)->delete();
$db->table('users')->insert([
    'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'first_name' => 'Clock', 'last_name' => 'Check', 'status' => 'active',
    'email_verified_at' => $now, 'created_at' => $now, 'updated_at' => $now,
]);
$userId = (int) $db->insertID();

$db->table('enrolments')->insert([
    'user_id' => $userId, 'course_id' => $courseId, 'mode' => 'SELF_PACED',
    'status' => 'active', 'source' => 'test', 'enrolled_at' => $now,
    'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 365),
    'created_at' => $now, 'updated_at' => $now,
]);
$enrolmentId = (int) $db->insertID();

$jar = tempnam(sys_get_temp_dir(), 'clock');

/** The value the framework actually stored, read out of its own session file. */
$stamp = static function () use ($jar, $quizId): ?int {
    foreach (file($jar) as $line) {
        $cols = preg_split('/\s+/', trim($line));
        if (($cols[5] ?? '') === 'ci_session') {
            $file = WRITEPATH . 'session/ci_session' . $cols[6];
            if (! is_file($file)) {
                return null;
            }
            if (preg_match('/quiz_started_' . $quizId . '\|i:(\d+);/', (string) file_get_contents($file), $m)) {
                return (int) $m[1];
            }
        }
    }

    return null;
};

$get = static function (string $path) use ($base, $jar): string {
    $out = shell_exec(sprintf(
        'curl -s -L -c %s -b %s %s 2>/dev/null',
        escapeshellarg($jar), escapeshellarg($jar), escapeshellarg($base . $path)
    ));

    return (string) $out;
};

$fail  = 0;
$check = static function (string $what, $got, $want) use (&$fail): void {
    $ok = $got === $want;
    printf("  %-52s %s (got %s, want %s)\n", $what, $ok ? 'ok' : 'FAIL', var_export($got, true), var_export($want, true));
    $fail += $ok ? 0 : 1;
};

try {
    // ── Sign in ─────────────────────────────────────────────────────────────
    $form = $get('/en/account/login');
    if (! preg_match('/name="([a-z0-9_]*csrf[a-z0-9_]*)"\s+value="([^"]+)"/i', $form, $m)
        && ! preg_match('/name="([^"]+)"\s+type="hidden"\s+value="([^"]+)"/i', $form, $m)) {
        fwrite(STDERR, "could not find the CSRF field on the login form\n");
        exit(2);
    }
    shell_exec(sprintf(
        'curl -s -L -c %s -b %s -d %s -d %s -d %s %s 2>/dev/null',
        escapeshellarg($jar), escapeshellarg($jar),
        escapeshellarg($m[1] . '=' . $m[2]),
        escapeshellarg('email=' . $email),
        escapeshellarg('password=' . $password),
        escapeshellarg($base . '/en/account/login')
    ));

    $lesson = $get('/en/learn/' . $course['slug'] . '/clock-check');
    $check('the lesson page renders for the signed-in learner',
        str_contains($lesson, 'Is the clock running?'), true);

    $first = $stamp();
    $check('the first render starts the clock', is_int($first), true);

    // Two seconds is enough: the question is whether the stamp moves at all.
    sleep(2);
    $get('/en/learn/' . $course['slug'] . '/clock-check');
    $second = $stamp();

    $check('the second render leaves the clock alone', $second, $first);

    // A window already past its limit is dead, and must be restamped rather
    // than met with "your time expired" before a question is answered.
    foreach (file($jar) as $line) {
        $cols = preg_split('/\s+/', trim($line));
        if (($cols[5] ?? '') === 'ci_session') {
            $file = WRITEPATH . 'session/ci_session' . $cols[6];
            $body = (string) file_get_contents($file);
            $dead = time() - 3600;
            file_put_contents($file, preg_replace(
                '/quiz_started_' . $quizId . '\|i:\d+;/',
                'quiz_started_' . $quizId . '|i:' . $dead . ';',
                $body
            ));
        }
    }
    $get('/en/learn/' . $course['slug'] . '/clock-check');
    $third = $stamp();
    $check('a dead window gets a fresh clock', is_int($third) && $third > time() - 60, true);
} finally {
    @unlink($jar);
    $db->table('quiz_questions')->where('id', $questionId)->delete();
    $db->table('quizzes')->where('id', $quizId)->delete();
    $db->table('lessons')->where('id', $lessonId)->delete();
    $db->table('course_modules')->where('id', $moduleId)->delete();
    $db->table('enrolments')->where('id', $enrolmentId)->delete();
    $db->table('users')->where('id', $userId)->delete();
}

echo "\n", $fail === 0 ? "all checks passed\n" : "$fail check(s) FAILED\n";
exit($fail === 0 ? 0 : 1);
