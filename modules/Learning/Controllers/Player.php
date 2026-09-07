<?php

namespace Modules\Learning\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use Modules\Account\Libraries\LearnerAuth;
use Modules\Catalog\Libraries\Schema;
use Modules\Catalog\Models\CourseModel;
use Modules\Commerce\Services\PricingService;
use Modules\Learning\Models\CertificateModel;
use Modules\Learning\Models\EnrolmentModel;
use Modules\Learning\Models\LessonModel;
use Modules\Learning\Models\ProgressModel;
use Modules\Learning\Models\QuizModel;
use Modules\Learning\Services\CertificateService;

/**
 * The self-paced player: the outline, one lesson, the progress the lesson
 * reports, the quiz at the end of it, the files that come with it — and, on
 * its own outside the gate, the one free lesson that sells the rest.
 *
 * **Access is the whole job of this class.** The `learner` filter on the route
 * group answers "who is asking". It does not answer "did they buy this", and
 * conflating the two is how a signed-in visitor who once downloaded a free
 * resource walks the slugs and reads a library the school sells. So every
 * method below re-asks the second question against `enrolments` —
 * `EnrolmentModel::hasAccess()` — including the three that are not pages and
 * are therefore the ones that get forgotten:
 *
 *   - `progress()` writes a row keyed by course and lesson;
 *   - `quiz()` marks an assessment and can issue a certificate;
 *   - `asset()` hands over a file.
 *
 * A failed check is a 404, never a 403. `/learn/premium-course` answering
 * "forbidden" confirms the course exists and that this account is not on it,
 * which is more than a stranger needs to be told; it answers "no such page",
 * which is what the rest of this codebase already does for orders, invoices and
 * joining links.
 *
 * **The course is looked up without the published scope.** An enrolment is an
 * entitlement, and an editor unpublishing a course while somebody is half way
 * through it must not lock them out of what they paid for. Soft deletes still
 * apply through the model, so a course genuinely removed is genuinely gone.
 *
 * **Nothing here fulfils, marks paid, or touches a seat.** The one thing this
 * controller creates is a certificate, through `CertificateService::issue()`,
 * which re-checks eligibility from the records itself and declines rather than
 * trusting the caller. Seats belong to `InventoryService` and payment belongs
 * to `EnrolmentService::fulfil()`, reached only from a verified webhook.
 */
class Player extends BaseController
{
    /**
     * Heartbeats accepted per minute, per learner, per lesson.
     *
     * The player posts its position on a fifteen-second timer, on pause, and
     * when the page is hidden — about five a minute in normal watching. The
     * ceiling is there so a stuck tab, a duplicated component or somebody
     * holding down the seek bar cannot turn one viewer into a write amplifier
     * on a table that is already the busiest in the schema.
     */
    private const HEARTBEATS_PER_MINUTE = 12;

    /** Completions per minute. Marking a lesson complete is a click, not a timer. */
    private const COMPLETIONS_PER_MINUTE = 10;

    /**
     * Grace on a timed assessment, in seconds.
     *
     * A learner who is thirty seconds over because the page took a moment to
     * post has not cheated. Anything past the grace is not marked *and does not
     * consume an attempt* — losing one of three attempts to a tab left open
     * over lunch is a punishment for something that is not an offence.
     */
    private const QUIZ_GRACE_SEC = 120;

    // ── The outline ─────────────────────────────────────────────────────────

    /**
     * Everything in the course, where the learner has got to in it, and one
     * button that goes to the right place.
     *
     * The resume target is the first lesson in course order that is not
     * complete, rather than the last one touched. The two differ only when
     * somebody has skipped ahead, and in that case the first-incomplete rule is
     * the kinder one: it never sends a learner past a lesson they have not
     * seen, and skipping forward again costs one click.
     */
    public function course(?string $locale = null, ?string $courseSlug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $course   = $this->entitled($courseSlug);
        $courseId = (int) $course['id'];
        $userId   = (int) LearnerAuth::id();

        $progress = (new ProgressModel())->forCourse($userId, $courseId);
        $outline  = $this->decorate((new LessonModel())->outline($courseId), $progress);

        $flat  = $this->flatten($outline);
        $total = count($flat);
        $done  = count(array_filter($flat, static fn (array $l): bool => $l['state'] === 'completed'));

        // The first incomplete lesson, or — when everything is done — the last
        // one, so the button reads "review" rather than disappearing.
        $resume = null;
        foreach ($flat as $lesson) {
            if ($lesson['state'] !== 'completed') {
                $resume = $lesson;
                break;
            }
        }
        $resume ??= ($flat === [] ? null : $flat[$total - 1]);

        return view('Modules\Learning\Views\player\course', [
            'course'      => $course,
            'outline'     => $outline,
            'total'       => $total,
            'done'        => $done,
            // Computed here rather than read from ProgressModel::percent(),
            // which counts every published lesson including any this outline
            // could not place. The two agree; taking it from the list on screen
            // means the ring can never disagree with the ticks under it.
            'percent'     => $total === 0 ? 0 : (int) round($done / $total * 100),
            'started'     => $done > 0 || array_filter($flat, static fn (array $l): bool => $l['state'] === 'started') !== [],
            'resume'      => $resume,
            'certificate' => (new CertificateModel())
                ->where('user_id', $userId)->where('course_id', $courseId)
                ->orderBy('id', 'DESC')->first(),
            'hasFinal'    => $this->hasFinalQuiz($courseId),
            'crumbs'      => [
                ['label' => lang('Learning.player.crumb_account'), 'url' => locale_url('account')],
                ['label' => lang('Learning.player.crumb_courses'), 'url' => locale_url('account/courses')],
                ['label' => t_field($course['title'])],
            ],
            'title'       => t_field($course['title']) . ' — ' . lang('Learning.player.title'),
            // Every page behind the gate. A learner's progress is not something
            // to offer a crawler, and these URLs are useless to one anyway.
            'noIndex'     => true,
        ]);
    }

    // ── One lesson ──────────────────────────────────────────────────────────

    /**
     * The player itself.
     *
     * Two things are resolved here rather than in the view, because both are
     * decisions rather than presentation: what the video element can honestly
     * show (see `video()`), and how many attempts are left on the quiz, which
     * the view must not compute from a count it could get wrong.
     *
     * The quiz questions arrive through `QuizModel::questionsForDisplay()`,
     * which strips `answer_json` and `explanation`. Nothing on this page has
     * ever held the answers, so no amount of reading the source finds them.
     */
    public function lesson(?string $locale = null, ?string $courseSlug = null, ?string $lessonSlug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $course   = $this->entitled($courseSlug);
        $courseId = (int) $course['id'];
        $userId   = (int) LearnerAuth::id();

        $lessons = new LessonModel();
        $lesson  = $lessons->findInCourse($courseId, (string) $lessonSlug);
        if ($lesson === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $lessonId = (int) $lesson['id'];
        $progress = (new ProgressModel())->forCourse($userId, $courseId);
        $outline  = $this->decorate($lessons->outline($courseId), $progress);
        $flat     = $this->flatten($outline);

        // Previous and next come from the flat list, not from sort_order
        // arithmetic: sort orders have gaps, duplicates and negative numbers in
        // any catalogue an editor has touched, and neighbour-by-index cannot.
        $index = null;
        foreach ($flat as $i => $row) {
            if ((int) $row['id'] === $lessonId) {
                $index = $i;
                break;
            }
        }

        $quizzes  = new QuizModel();
        $quiz     = $quizzes->forLesson($lessonId);
        $attempts = $quiz === null ? [] : $this->attempts($userId, (int) $quiz['id']);

        if ($quiz !== null) {
            // The clock starts when the questions are first rendered, and it is
            // stored in the session where the learner cannot edit it. A hidden
            // field carrying the start time would be a hidden field carrying
            // whatever the learner would like the start time to have been.
            //
            // Stamped only when no attempt is in flight. Re-stamping on every
            // render — which is what this did — left the limit unenforceable:
            // opening the lesson in a second tab, or simply refreshing it at
            // minute 29 of 30, put the clock back to zero, and a timed
            // assessment with a resettable clock is an untimed one.
            //
            // A window that is already past its limit is dead and gets a fresh
            // stamp, so somebody returning to a lesson days later is not met
            // with "your time expired" before they have answered anything.
            $key     = 'quiz_started_' . (int) $quiz['id'];
            $started = session()->get($key);
            $limit   = (int) ($quiz['time_limit_min'] ?? 0);

            if (! is_int($started)
                || ($limit > 0 && time() - $started > $limit * 60 + self::QUIZ_GRACE_SEC)) {
                session()->set($key, time());
            }
        }

        $current = $progress[$lessonId] ?? null;

        return view('Modules\Learning\Views\player\lesson', [
            'course'      => $course,
            'lesson'      => $lesson,
            'outline'     => $outline,
            'position'    => $index,
            'total'       => count($flat),
            'previous'    => $index !== null && $index > 0 ? $flat[$index - 1] : null,
            'next'        => $index !== null && $index + 1 < count($flat) ? $flat[$index + 1] : null,
            'video'       => $this->video($lesson),
            'assets'      => $this->assets($lessonId),
            'quiz'        => $quiz,
            'questions'   => $quiz === null ? [] : $quizzes->questionsForDisplay((int) $quiz['id']),
            'attemptsUsed'    => count($attempts),
            'attemptsAllowed' => $quiz === null ? 0 : (int) $quiz['attempts_allowed'],
            'passedAlready'   => array_filter($attempts, static fn (array $a): bool => (int) $a['passed'] === 1) !== [],
            // Flashed by quiz(), which redirects rather than rendering, so a
            // refresh after submitting cannot re-post the answers.
            'result'      => session()->getFlashdata('quiz_result'),
            'startAt'     => (int) ($current['position_sec'] ?? 0),
            'completed'   => ($current['status'] ?? '') === 'completed',
            'percent'     => (new ProgressModel())->percent($userId, $courseId),
            'progressUrl' => locale_url('learn/' . $course['slug'] . '/' . $lesson['slug'] . '/progress'),
            'quizUrl'     => locale_url('learn/' . $course['slug'] . '/' . $lesson['slug'] . '/quiz'),
            'crumbs'      => [
                ['label' => lang('Learning.player.crumb_courses'), 'url' => locale_url('account/courses')],
                ['label' => t_field($course['title']), 'url' => locale_url('learn/' . $course['slug'])],
                ['label' => t_field($lesson['title'])],
            ],
            'title'       => t_field($lesson['title']) . ' — ' . t_field($course['title']),
            'noIndex'     => true,
        ]);
    }

    // ── Progress ────────────────────────────────────────────────────────────

    /**
     * Where the learner has got to in this lesson.
     *
     * Answers JSON to the player and a redirect to a form, because the
     * "mark complete" control is a real form that works with JavaScript
     * switched off and is merely intercepted when it is not.
     *
     * Rate-limited on purpose. A video player reports its position every few
     * seconds and every one of those is an upsert on the busiest table in the
     * schema; without a ceiling, one learner leaving a tab open overnight is
     * tens of thousands of writes that record nothing at all. The limit is per
     * learner *and* per lesson, so one throttled lesson cannot stop another.
     *
     * The response carries a fresh CSRF token. `Config\Security::$regenerate`
     * is on, so every accepted POST invalidates the token sitting in the page's
     * forms — a player that heartbeats for five minutes and then submits the
     * quiz would otherwise be refused, and the failure would look like a bug in
     * the quiz. The player writes this value back into every token field on the
     * page. See `player/lesson.php`.
     */
    public function progress(?string $locale = null, ?string $courseSlug = null, ?string $lessonSlug = null)
    {
        helper(['norlanka', 'url']);

        $course = $this->entitled($courseSlug);
        $lesson = (new LessonModel())->findInCourse((int) $course['id'], (string) $lessonSlug);
        if ($lesson === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userId   = (int) LearnerAuth::id();
        $courseId = (int) $course['id'];
        $lessonId = (int) $lesson['id'];
        $complete = $this->request->getPost('complete') !== null;

        // Two buckets, not one. A heartbeat and a deliberate click are
        // different traffic, and a learner who has been watching for an hour
        // must still be able to press the button.
        $throttler = service('throttler');
        $bucket    = 'learn-' . ($complete ? 'done' : 'beat') . '-' . $userId . '-' . $lessonId;
        $ceiling   = $complete ? self::COMPLETIONS_PER_MINUTE : self::HEARTBEATS_PER_MINUTE;

        if ($throttler->check($bucket, $ceiling, MINUTE) === false) {
            // 429 rather than an error: nothing has gone wrong, the client is
            // simply early. The player treats it as "keep the position, try
            // again on the next tick" and shows the learner nothing.
            return $this->request->isAJAX()
                ? $this->response->setStatusCode(429)->setJSON(['ok' => false, 'reason' => 'throttled', 'csrf' => csrf_hash()])
                : redirect()->to(locale_url('learn/' . $course['slug'] . '/' . $lesson['slug']));
        }

        // Clamped. The position is only ever used to resume playback, but it is
        // a number a client chose and it lands in an INT column; a lesson whose
        // duration is known cannot be resumed from beyond its own end.
        $position = max(0, (int) $this->request->getPost('position'));
        $duration = (int) $lesson['duration_sec'];
        if ($duration > 0) {
            $position = min($position, $duration);
        }

        (new ProgressModel())->record($userId, $courseId, $lessonId, $position, $complete);

        $percent = (new ProgressModel())->percent($userId, $courseId);

        // Only when the last lesson has just been ticked off, and only for a
        // course with no final assessment to gate it — CertificateService
        // re-checks all of that and declines if it disagrees, so this is a
        // guard against doing the work, not against issuing wrongly.
        $issued = null;
        if ($complete && $percent === 100 && ! $this->hasFinalQuiz($courseId)) {
            $issued = $this->issueCertificate($userId, $courseId);
        }

        if (! $this->request->isAJAX()) {
            return redirect()->to(locale_url('learn/' . $course['slug'] . '/' . $lesson['slug']));
        }

        return $this->response->setJSON([
            'ok'          => true,
            'position'    => $position,
            'completed'   => $complete,
            'percent'     => $percent,
            'certificate' => $issued !== null,
            'csrf'        => csrf_hash(),
        ]);
    }

    // ── The quiz ────────────────────────────────────────────────────────────

    /**
     * Mark an attempt, server-side, and record it.
     *
     * The answers never reach the browser, so marking cannot happen there. What
     * comes back is a score and, when it is safe to give them, the explanations
     * — safe meaning the learner has passed, or has no attempts left. Handing
     * the worked answers back after a first failure on a three-attempt
     * assessment is handing over the answer sheet.
     *
     * Redirects rather than rendering, so refreshing the result page cannot
     * re-post the answers and spend a second attempt.
     */
    public function quiz(?string $locale = null, ?string $courseSlug = null, ?string $lessonSlug = null)
    {
        helper(['norlanka', 'url']);

        $course = $this->entitled($courseSlug);
        $lesson = (new LessonModel())->findInCourse((int) $course['id'], (string) $lessonSlug);
        if ($lesson === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $quizzes = new QuizModel();
        $quiz    = $quizzes->forLesson((int) $lesson['id']);
        if ($quiz === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userId  = (int) LearnerAuth::id();
        $quizId  = (int) $quiz['id'];
        $back    = locale_url('learn/' . $course['slug'] . '/' . $lesson['slug']);
        $allowed = (int) $quiz['attempts_allowed'];
        $used    = count($this->attempts($userId, $quizId));

        // Enforced here and nowhere else. The view hides the form when the
        // attempts are gone, but a hidden form is a suggestion and this is the
        // rule.
        if ($allowed > 0 && $used >= $allowed) {
            return redirect()->to($back)->with('error', lang('Learning.quiz.attempts_none', [$allowed]));
        }

        $startedAt = session()->get('quiz_started_' . $quizId);

        // Over time. The attempt is refused rather than failed, and no attempt
        // is consumed — see QUIZ_GRACE_SEC.
        if (! empty($quiz['time_limit_min']) && is_int($startedAt)
            && time() - $startedAt > (int) $quiz['time_limit_min'] * 60 + self::QUIZ_GRACE_SEC) {
            session()->remove('quiz_started_' . $quizId);

            return redirect()->to($back)->with('error', lang('Learning.quiz.expired'));
        }

        $answers = $this->readAnswers();
        $result  = $quizzes->mark($quizId, $answers);

        $db = db_connect();
        $db->table('quiz_attempts')->insert([
            'user_id'      => $userId,
            'quiz_id'      => $quizId,
            'enrolment_id' => $this->enrolmentId($userId, (int) $course['id']),
            'score'        => (int) $result['score'],
            'max_score'    => (int) $result['max'],
            'passed'       => $result['passed'] ? 1 : 0,
            // What was submitted, never what it was marked against. `detail`
            // carries the explanations, and a column that stores them is a
            // column that ends up in an export one day.
            'answers_json' => json_encode($answers),
            'started_at'   => is_int($startedAt) ? date('Y-m-d H:i:s', $startedAt) : null,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);

        session()->remove('quiz_started_' . $quizId);

        $left = $allowed > 0 ? max(0, $allowed - ($used + 1)) : null;

        // Passing the quiz completes its lesson. Somebody who has answered the
        // questions has plainly watched the video, and leaving the tick off
        // would leave the course at 90% forever.
        $issued = null;
        if ($result['passed']) {
            (new ProgressModel())->record(
                $userId,
                (int) $course['id'],
                (int) $lesson['id'],
                (int) $lesson['duration_sec'],
                true
            );

            if ((int) $quiz['is_final'] === 1) {
                $issued = $this->issueCertificate($userId, (int) $course['id']);
            }
        }

        // Unlocked by passing, or by running out of attempts. Anything else and
        // the learner sees a score and which questions were wrong, which is
        // enough to know what to study and not enough to reverse-engineer.
        $unlocked = $result['passed'] || $left === 0;

        return redirect()->to($back)->with('quiz_result', [
            'passed'      => (bool) $result['passed'],
            'score'       => (int) $result['score'],
            'max'         => (int) $result['max'],
            'percent'     => (int) $result['percent'],
            'left'        => $left,
            'unlocked'    => $unlocked,
            'detail'      => array_map(static fn (array $d): array => [
                'question_id' => (int) $d['question_id'],
                'correct'     => (bool) $d['correct'],
                'explanation' => $unlocked ? (string) ($d['explanation'] ?? '') : '',
            ], $result['detail']),
            'certificate' => $issued !== null,
        ]);
    }

    // ── Exercise files ──────────────────────────────────────────────────────

    /**
     * One lesson file, streamed through here.
     *
     * The join to `lessons` is the access control, and it is the reason this
     * method exists at all: asset ids are global, so without
     * `l.course_id = this course` a learner enrolled on one course could count
     * upwards and pull down every exercise file the school has ever published.
     *
     * The stored path is resolved against two roots and then checked to still
     * be inside the root it resolved against. `../` in a stored path is the
     * difference between an exercise file and whatever else the web user can
     * read, and the path came out of a database row rather than out of thin
     * air.
     *
     * Served as `application/octet-stream` — `download()`'s third argument left
     * false — because these are uploads. A stored `.html` served as text/html
     * from this origin is a cross-site scripting hole with a download button on
     * it.
     */
    public function asset(?string $locale = null, ?string $courseSlug = null, ?string $assetId = null)
    {
        helper(['norlanka', 'url']);

        $course = $this->entitled($courseSlug);

        $asset = db_connect()->table('lesson_assets la')
            ->select('la.id, la.path, la.label, la.kind')
            ->join('lessons l', 'l.id = la.lesson_id')
            ->where('la.id', (int) $assetId)
            ->where('l.course_id', (int) $course['id'])
            ->where('l.status', 'published')
            ->get()->getRowArray();

        if ($asset === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $path = $this->assetPath((string) $asset['path']);
        if ($path === null) {
            // A row pointing at a file that is not there is an editorial
            // problem, not a learner's. It is logged with the id so somebody
            // can fix it, and the learner gets the same 404 as a stranger
            // rather than a stack trace.
            log_message('error', 'Lesson asset {id} points at a missing or unreachable file: {path}', [
                'id'   => (int) $asset['id'],
                'path' => (string) $asset['path'],
            ]);

            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($path, null)->setFileName($this->downloadName($asset, $path));
    }

    // ── The free preview ────────────────────────────────────────────────────

    /**
     * One lesson, in full, to anybody.
     *
     * The only public method on this controller, and the gate is a single
     * column: `is_preview`. Everything else in the library is bought, so a
     * lesson that has not been marked as free is a 404 here no matter who is
     * asking — including a learner who owns the course, who has their own
     * address for it.
     *
     * The course must be *published* for this one, unlike everywhere else in
     * this class: this is a sales page, not an entitlement, and a draft course
     * has no business being indexed.
     *
     * The transcript is rendered as page text rather than hidden behind a tab.
     * It is the point: a preview lesson's transcript is the only part of a
     * video course a search engine can read, and it is what makes this address
     * worth having.
     */
    public function preview(?string $locale = null, ?string $courseSlug = null, ?string $lessonSlug = null)
    {
        helper(['norlanka', 'catalog', 'commerce', 'url']);

        $course = (new CourseModel())->findLive((string) $courseSlug);
        if ($course === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $courseId = (int) $course['id'];
        $lessons  = new LessonModel();
        $lesson   = $lessons->findInCourse($courseId, (string) $lessonSlug);

        if ($lesson === null || (int) $lesson['is_preview'] !== 1) {
            throw PageNotFoundException::forPageNotFound();
        }

        // Modules with nothing recorded in them are dropped: a heading with no
        // lessons under it reads as a course with holes in it, which is the
        // opposite of what this page is for.
        $outline = array_values(array_filter(
            $lessons->outline($courseId),
            static fn (array $module): bool => ($module['lessons'] ?? []) !== []
        ));

        $count   = 0;
        $runtime = 0;
        foreach ($outline as $module) {
            foreach ($module['lessons'] as $row) {
                $count++;
                $runtime += (int) $row['duration_sec'];
            }
        }

        $currency = current_currency();
        $price    = (new PricingService())->courseFromPrices($courseId, $currency)['SELF_PACED'] ?? null;

        // Somebody who already owns this sees a way into the real player rather
        // than a buy button for something they have paid for.
        $userId = (int) (LearnerAuth::id() ?? 0);
        $owned  = $userId > 0 && (new EnrolmentModel())->hasAccess($userId, $courseId);

        $crumbs = [
            ['label' => lang('Catalog.ondemand.title'), 'url' => locale_url('on-demand')],
            ['label' => t_field($course['title']), 'url' => locale_url('on-demand/' . $course['slug'])],
            ['label' => t_field($lesson['title'])],
        ];

        return view('Modules\Learning\Views\player\preview', [
            'course'          => $course,
            'lesson'          => $lesson,
            'outline'         => $outline,
            'lessonCount'     => $count,
            'runtimeSec'      => $runtime,
            'video'           => $this->video($lesson),
            'price'           => $price,
            'currency'        => $currency,
            'owned'           => $owned,
            'crumbs'          => $crumbs,
            // No VideoObject graph. It would have to assert a thumbnail, an
            // upload date and a content URL, and three of those would be
            // invented for a lesson whose file may not even be in place.
            'schema'          => Schema::render([Schema::organisation(), Schema::breadcrumbs($crumbs)]),
            'title'           => lang('Learning.preview.title', [t_field($lesson['title']), t_field($course['title'])])
                . ' — ' . setting('site_name', ''),
            'metaDescription' => lang('Learning.preview.meta', [t_field($lesson['title']), t_field($course['title'])]),
            'canonical'       => locale_url('preview/' . $course['slug'] . '/' . $lesson['slug']),
            'ogImage'         => $course['hero_image'] ? media_src($course['hero_image']) : null,
            'lastUpdated'     => $lesson['updated_at'] ?? $course['updated_at'],
        ]);
    }

    // ── Access ──────────────────────────────────────────────────────────────

    /**
     * The course this learner is allowed to open, or a 404.
     *
     * Called first in every gated method, including the three that return JSON
     * or bytes rather than a page. Deliberately one method, so there is one
     * place to read and one place to break — an access check copied into six
     * methods is an access check that is missing from one of them.
     */
    private function entitled(?string $courseSlug): array
    {
        $userId = (int) (LearnerAuth::id() ?? 0);

        // Not findLive(): see the class docblock. The model's soft-delete scope
        // still applies, so a deleted course is not found here either.
        $course = (new CourseModel())->where('courses.slug', (string) $courseSlug)->first();

        if ($course === null || $userId === 0) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! (new EnrolmentModel())->hasAccess($userId, (int) $course['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $course;
    }

    // ── Internals ───────────────────────────────────────────────────────────

    /**
     * Attach each lesson's state, position, quiz and file count to the outline.
     *
     * Three queries for the whole course rather than three per lesson: the same
     * work asked inside the loop is a query count that grows with the length of
     * the curriculum, on the page a learner opens most often.
     *
     * @param list<array>        $outline  from LessonModel::outline()
     * @param array<int, array>  $progress from ProgressModel::forCourse()
     * @return list<array>
     */
    private function decorate(array $outline, array $progress): array
    {
        $ids = [];
        foreach ($outline as $module) {
            foreach ($module['lessons'] ?? [] as $lesson) {
                $ids[] = (int) $lesson['id'];
            }
        }

        if ($ids === []) {
            return $outline;
        }

        $db = db_connect();

        $quizRows = $db->table('quizzes')->select('lesson_id, is_final')
            ->whereIn('lesson_id', $ids)->get()->getResultArray();
        $quizzes = array_column($quizRows, 'is_final', 'lesson_id');

        $assetRows = $db->table('lesson_assets')
            ->select('lesson_id, COUNT(*) AS files', false)
            ->whereIn('lesson_id', $ids)
            ->groupBy('lesson_id')
            ->get()->getResultArray();
        $assets = array_column($assetRows, 'files', 'lesson_id');

        foreach ($outline as &$module) {
            // `foreach ($module['lessons'] ?? [] as &$lesson)` would iterate a
            // temporary copy and throw every assignment below away without a
            // warning of any kind — the decorated fields simply never appear
            // and the outline renders with every lesson "not started". The
            // guard keeps the loop over a real, referenceable variable.
            if (! isset($module['lessons']) || ! is_array($module['lessons'])) {
                continue;
            }

            foreach ($module['lessons'] as &$lesson) {
                $id     = (int) $lesson['id'];
                $status = (string) ($progress[$id]['status'] ?? '');

                $lesson['state']    = $status === 'completed' ? 'completed' : ($status === 'started' ? 'started' : 'todo');
                $lesson['position'] = (int) ($progress[$id]['position_sec'] ?? 0);
                $lesson['has_quiz'] = isset($quizzes[$id]);
                $lesson['is_final'] = isset($quizzes[$id]) && (int) $quizzes[$id] === 1;
                $lesson['files']    = (int) ($assets[$id] ?? 0);
            }
            unset($lesson);
        }
        unset($module);

        return $outline;
    }

    /**
     * The outline as one ordered list, which is the order the player walks.
     *
     * @param list<array> $outline
     * @return list<array>
     */
    private function flatten(array $outline): array
    {
        $flat = [];
        foreach ($outline as $module) {
            foreach ($module['lessons'] ?? [] as $lesson) {
                $flat[] = $lesson;
            }
        }

        return $flat;
    }

    /**
     * What the player can honestly show for this lesson.
     *
     * `media` is a file in this site's own library and plays from disk. `mux`
     * and `bunny` are in the schema because the blueprint names them; neither
     * is contracted, neither has an adapter, and a signed playback id is not a
     * URL. Rendering one as a source produces a frame that fails without saying
     * anything, and a silent failure on a paid lesson is indistinguishable from
     * a course that does not work. So they are reported as unconfigured and the
     * view says so in a sentence.
     *
     * @return array{state:string, src:?string, provider:?string}
     *         state ∈ none | ready | missing | unconfigured
     */
    private function video(array $lesson): array
    {
        $provider = strtolower(trim((string) ($lesson['video_provider'] ?? '')));
        $ref      = trim((string) ($lesson['video_ref'] ?? ''));

        // A reading or quiz lesson has no video and is not missing one.
        if ((string) $lesson['type'] !== 'video') {
            return ['state' => 'none', 'src' => null, 'provider' => null];
        }

        // A video lesson with nothing attached is missing its recording, which
        // is a different thing from not having one, and the learner is told so
        // rather than shown an empty column where a player should be.
        if ($provider === '' || $ref === '') {
            return ['state' => 'missing', 'src' => null, 'provider' => $provider ?: null];
        }

        if ($provider !== 'media') {
            return ['state' => 'unconfigured', 'src' => null, 'provider' => $provider];
        }

        // media_src() returns the path unchanged when the file is not there, so
        // it cannot answer this question; the file is checked directly.
        $ref = '/' . ltrim($ref, '/');
        if (! is_file(FCPATH . ltrim($ref, '/'))) {
            return ['state' => 'missing', 'src' => null, 'provider' => $provider];
        }

        return ['state' => 'ready', 'src' => media_src($ref), 'provider' => $provider];
    }

    /** @return list<array> */
    private function assets(int $lessonId): array
    {
        return db_connect()->table('lesson_assets')
            ->where('lesson_id', $lessonId)
            ->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    /** @return list<array> */
    private function attempts(int $userId, int $quizId): array
    {
        return db_connect()->table('quiz_attempts')
            ->where('user_id', $userId)->where('quiz_id', $quizId)
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();
    }

    private function hasFinalQuiz(int $courseId): bool
    {
        return db_connect()->table('quizzes q')
            ->join('lessons l', 'l.id = q.lesson_id')
            ->where('l.course_id', $courseId)
            ->where('l.status', 'published')
            ->where('q.is_final', 1)
            ->countAllResults() > 0;
    }

    /**
     * The submitted answers, reduced to what the marker can be trusted with.
     *
     * Keys must be question ids and values must be scalars or a list of them.
     * `QuizModel::mark()` compares them as strings and never evaluates them,
     * but a nested array arriving where a string is expected is the sort of
     * thing that turns a comparison into a warning in a log nobody reads.
     *
     * @return array<int, string|list<string>>
     */
    private function readAnswers(): array
    {
        $posted = $this->request->getPost('answers');
        if (! is_array($posted)) {
            return [];
        }

        $clean = [];
        foreach ($posted as $questionId => $given) {
            if (! is_numeric($questionId)) {
                continue;
            }

            if (is_array($given)) {
                $clean[(int) $questionId] = array_values(array_map(
                    'strval',
                    array_filter($given, static fn ($v): bool => is_scalar($v))
                ));
                continue;
            }

            if (is_scalar($given)) {
                $clean[(int) $questionId] = (string) $given;
            }
        }

        return $clean;
    }

    /**
     * The enrolment this learner's work here belongs to, or 0 when it has gone.
     *
     * The same row `entitled()` let them in on — see
     * EnrolmentModel::accessRowFor() for why picking a different one silently
     * costs self-paced learners their certificates.
     */
    private function enrolmentId(int $userId, int $courseId): int
    {
        $row = (new EnrolmentModel())->accessRowFor($userId, $courseId);

        return (int) ($row['id'] ?? 0);
    }

    /**
     * Ask for a certificate, and accept being told no.
     *
     * `CertificateService::issue()` re-runs its own eligibility rules against
     * the records and returns null when they are not met, so this is a request
     * rather than an instruction. It is deliberately never forced from here:
     * `$force` exists for an administrator who knows somebody was in the room,
     * and a controller reachable by the holder of the certificate is not that.
     */
    private function issueCertificate(int $userId, int $courseId): ?array
    {
        $enrolmentId = $this->enrolmentId($userId, $courseId);
        if ($enrolmentId === 0) {
            return null;
        }

        try {
            return (new CertificateService())->issue($enrolmentId);
        } catch (\Throwable $e) {
            // A certificate that could not be rendered must not turn a passed
            // assessment into an error page. The pass is recorded either way
            // and the account area re-offers the download.
            log_message('error', 'Certificate issue failed for enrolment {id}: {msg}', [
                'id'  => $enrolmentId,
                'msg' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * The stored path, resolved to a real file inside a root we allow.
     *
     * Two roots because material arrives two ways: anything uploaded through
     * the media library lands under `public/`, and anything an administrator
     * deliberately kept off the web root lands under `writable/uploads/`. Both
     * are resolved with realpath() and then checked to still start with their
     * root — resolving alone proves nothing, since `../../` resolves perfectly
     * well to somewhere it should not.
     */
    private function assetPath(string $stored): ?string
    {
        $stored = trim($stored);
        if ($stored === '' || str_contains($stored, "\0")) {
            return null;
        }

        foreach ([FCPATH, WRITEPATH . 'uploads'] as $candidate) {
            $root = realpath($candidate);
            if ($root === false) {
                continue;
            }

            $full = realpath(rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($stored, '/\\'));
            if ($full === false || ! is_file($full)) {
                continue;
            }

            if (str_starts_with($full, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                return $full;
            }
        }

        return null;
    }

    /**
     * What the file is called when it lands in somebody's downloads folder.
     *
     * Built from the editor's label rather than from the stored path, because
     * "photoshop-exercise-01.zip" is more use than "a3f9c2.zip" — but stripped
     * hard, because it is interpolated into a response header and the label
     * came out of a text column.
     */
    private function downloadName(array $asset, string $path): string
    {
        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?: '';

        $base = (string) preg_replace('/[^A-Za-z0-9 ._-]+/', '', (string) ($asset['label'] ?? ''));
        $base = trim((string) preg_replace('/[\s_]+/', '-', $base), '-._');

        if ($base === '') {
            $base = (string) preg_replace('/[^A-Za-z0-9._-]+/', '', (string) pathinfo($path, PATHINFO_FILENAME));
        }
        if ($base === '') {
            $base = 'lesson-file';
        }

        $base = mb_substr($base, 0, 80);

        return $extension === '' ? $base : $base . '.' . $extension;
    }
}
