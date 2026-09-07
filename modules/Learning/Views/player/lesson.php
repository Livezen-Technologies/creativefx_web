<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One lesson: the recording, what it says, what comes with it, and the way on.
 *
 * Four decisions are visible on this page and each is here rather than in the
 * controller only because this is where they are seen:
 *
 * **The video is honest about what it cannot do.** `mux` and `bunny` are in the
 * schema and neither is contracted, so a lesson pointing at one gets a named
 * panel saying the recording is not connected yet — not an empty frame. A frame
 * that fails silently on a course somebody paid for is indistinguishable from a
 * broken site, and the transcript and the files below it still work.
 *
 * **The transcript is on the page, not behind a tab.** It is the part of a
 * video lesson that can be read, searched, copied and used by somebody who
 * cannot or does not want to watch. Hiding it behind an accordion to tidy the
 * layout is tidying away the accessible version of the content.
 *
 * **Mark-complete is a real form.** It posts to the same endpoint the player's
 * heartbeat uses and works with JavaScript switched off; Alpine merely
 * intercepts it. The two states are rendered server-side with matching inline
 * `display` so the swap has no flash and the no-JS case is already correct.
 *
 * **The quiz form holds no answers.** `QuizModel::questionsForDisplay()` strips
 * `answer_json` and `explanation` before anything reaches this file, so there
 * is nothing here to read out of the source. Marking happens on the server and
 * the explanations only come back once passing or a spent last attempt makes
 * them safe to give.
 *
 * @var array       $course
 * @var array       $lesson
 * @var list<array> $outline
 * @var int|null    $position   this lesson's index in the flat order
 * @var int         $total
 * @var array|null  $previous
 * @var array|null  $next
 * @var array       $video      state ∈ none|ready|missing|unconfigured
 * @var list<array> $assets
 * @var array|null  $quiz
 * @var list<array> $questions  answers already stripped
 * @var int         $attemptsUsed
 * @var int         $attemptsAllowed
 * @var bool        $passedAlready
 * @var array|null  $result     the marked attempt, flashed by Player::quiz()
 * @var int         $startAt    seconds
 * @var bool        $completed
 * @var int         $percent
 * @var string      $progressUrl
 * @var string      $quizUrl
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$clock = static function (int $seconds): string {
    if ($seconds <= 0) {
        return '';
    }
    $minutes = max(1, (int) round($seconds / 60));

    return $minutes < 60
        ? lang('Learning.clock.min', [$minutes])
        : lang('Learning.clock.hour', [intdiv($minutes, 60), str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT)]);
};

/** mm:ss, for the resume note — the shape a video scrubber shows. */
$timecode = static function (int $seconds): string {
    $seconds = max(0, $seconds);
    $hours   = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $rest    = $seconds % 60;

    return ($hours > 0 ? $hours . ':' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT) : (string) $minutes)
        . ':' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT);
};

$size = static function ($bytes): string {
    $bytes = (int) $bytes;
    if ($bytes <= 0) {
        return '';
    }
    $units = ['B', 'KB', 'MB', 'GB'];
    $index = 0;
    $value = (float) $bytes;
    while ($value >= 1024 && $index < count($units) - 1) {
        $value /= 1024;
        $index++;
    }

    return ($index === 0 ? (string) (int) $value : number_format($value, $value < 10 ? 1 : 0)) . ' ' . $units[$index];
};

$lessonUrl = static fn (array $row): string => locale_url('learn/' . $course['slug'] . '/' . $row['slug']);

// Attempts remaining. `attempts_allowed` of 0 means unlimited, which is a
// setting an editor can choose and not a course with no attempts on it.
$attemptsLeft = $attemptsAllowed > 0 ? max(0, $attemptsAllowed - $attemptsUsed) : null;
$quizOpen     = $quiz !== null && $questions !== [] && ! $passedAlready && ($attemptsLeft === null || $attemptsLeft > 0);

// Marked-attempt feedback, keyed by question so the loop below can find it.
$marks = [];
foreach ($result['detail'] ?? [] as $row) {
    $marks[(int) $row['question_id']] = $row;
}
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => t_field($course['title']),
    'heading' => t_field($lesson['title']),
    'intro'   => $position === null ? '' : lang('Learning.lesson.of', [$position + 1, $total]),
], ['saveData' => false]) ?>

<?php // The Alpine root wraps both columns, so the progress figure in the
      // sidebar moves when the button in the main column is pressed.
      //
      // The component is at the foot of this file rather than in this
      // attribute, and its configuration goes with it. Two reasons, and the
      // second is the one that bites: the code carries `<` and `>`, which are
      // legal inside a quoted attribute and mishandled by every naive parser in
      // a toolchain; and a JSON blob cannot be made safe for an attribute by
      // escaping its quotes, because those quotes are the JSON's own structure
      // and escaping them yields JavaScript that will not parse. In a script
      // block neither problem exists. ?>
<div
    class="container-x grid gap-10 py-14 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-14"
    x-data="lessonPlayer()"
>

    <div class="min-w-0 space-y-12">

        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>

        <?php // ── The recording ──────────────────────────────────────────── ?>
        <?php if ($video['state'] === 'ready'): ?>
            <div>
                <div class="overflow-hidden rounded-2xl border border-line bg-brand-black">
                    <video
                        x-ref="video"
                        class="block aspect-video w-full"
                        controls
                        playsinline
                        preload="metadata"
                        <?php if ((int) $lesson['duration_sec'] > 0): ?>aria-describedby="lesson-runtime"<?php endif; ?>
                    >
                        <source src="<?= esc($video['src'], 'attr') ?>">
                        <?= esc(lang('Learning.lesson.video_no_js')) ?>
                    </video>
                </div>

                <p class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-white/55">
                    <?php if ((int) $lesson['duration_sec'] > 0): ?>
                        <span id="lesson-runtime" class="tabular-nums"><?= esc($clock((int) $lesson['duration_sec'])) ?></span>
                        <span aria-hidden="true">·</span>
                    <?php endif; ?>
                    <span><?= esc(lang('Learning.lesson.progress_saved')) ?></span>
                </p>

                <?php if ($startAt > 10 && ! $completed): ?>
                    <p class="mt-1 text-xs text-white/45"><?= esc(lang('Learning.lesson.resume_from', [$timecode($startAt)])) ?></p>
                <?php endif; ?>
            </div>

        <?php elseif ($video['state'] === 'unconfigured' || $video['state'] === 'missing'): ?>
            <?php // Named, not blank. See the docblock: a dead frame on a paid
                  // lesson is read as a broken course. ?>
            <section class="rounded-2xl border border-line bg-surface p-7">
                <h2 class="text-lg font-semibold">
                    <?= esc($video['state'] === 'unconfigured'
                        ? lang('Learning.lesson.video_unconfigured_heading')
                        : lang('Learning.lesson.video_missing_heading')) ?>
                </h2>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/70">
                    <?= esc($video['state'] === 'unconfigured'
                        ? lang('Learning.lesson.video_unconfigured_body')
                        : lang('Learning.lesson.video_missing_body')) ?>
                </p>
            </section>
        <?php endif; ?>

        <?php // ── Mark complete, and the way on ──────────────────────────── ?>
        <div class="flex flex-wrap items-center gap-4">
            <?php // Both states are rendered, and the inline display matches
                  // what x-show would set — so there is no flash on load and no
                  // JavaScript is needed for the correct one to be showing. ?>
            <p class="inline-flex items-center gap-2 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-2.5 text-sm font-semibold"
               x-show="completed" <?= $completed ? '' : 'style="display:none"' ?>>
                <svg class="h-4 w-4 text-brand-red" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10.5l4 4 8-9" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <?= esc(lang('Learning.lesson.completed')) ?>
            </p>

            <form method="post" action="<?= esc($progressUrl, 'attr') ?>"
                  x-show="! completed" <?= $completed ? 'style="display:none"' : '' ?>
                  @submit.prevent="send(true)">
                <?= csrf_field() ?>
                <input type="hidden" name="complete" value="1">
                <input type="hidden" name="position" value="<?= (int) $startAt ?>">
                <button type="submit" class="btn-brand" :disabled="saving">
                    <span x-show="! saving"><?= esc(lang('Learning.lesson.mark_complete')) ?></span>
                    <span x-show="saving" style="display:none"><?= esc(lang('Learning.lesson.marking')) ?></span>
                </button>
            </form>

            <p role="alert" class="text-sm font-medium text-brand-red" x-show="failed" style="display:none">
                <?= esc(lang('Learning.lesson.mark_failed')) ?>
            </p>
        </div>

        <?php // ── Notes ──────────────────────────────────────────────────── ?>
        <?php if ($body = rich_text($lesson['body'])): ?>
            <section aria-labelledby="notes">
                <h2 id="notes" class="section-title"><?= esc(lang('Learning.lesson.notes')) ?></h2>
                <div class="prose-site mt-5"><?= $body ?></div>
            </section>
        <?php endif; ?>

        <?php // ── Transcript ─────────────────────────────────────────────── ?>
        <section aria-labelledby="transcript">
            <h2 id="transcript" class="section-title"><?= esc(lang('Learning.lesson.transcript')) ?></h2>
            <?php if ($transcript = rich_text($lesson['transcript'])): ?>
                <div class="prose-site mt-5 max-w-none"><?= $transcript ?></div>
            <?php else: ?>
                <p class="mt-4 text-white/60"><?= esc(lang('Learning.lesson.transcript_none')) ?></p>
            <?php endif; ?>
        </section>

        <?php // ── Exercise files ─────────────────────────────────────────── ?>
        <section aria-labelledby="downloads">
            <h2 id="downloads" class="section-title"><?= esc(lang('Learning.lesson.downloads')) ?></h2>

            <?php if ($assets === []): ?>
                <p class="mt-4 text-white/60"><?= esc(lang('Learning.lesson.downloads_none')) ?></p>
            <?php else: ?>
                <ul class="mt-5 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                    <?php foreach ($assets as $asset): ?>
                        <?php // The link is to the controller, never to the
                              // stored path. The file may not be under the web
                              // root at all, and even when it is, the enrolment
                              // check is the only thing keeping it bought. ?>
                        <li>
                            <a href="<?= esc(locale_url('learn/' . $course['slug'] . '/asset/' . (int) $asset['id'])) ?>"
                               class="group flex items-center gap-4 px-5 py-4 transition hover:bg-brand-red/5">
                                <svg class="h-5 w-5 shrink-0 text-white/40 transition group-hover:text-brand-red" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 3v10m0 0l-3.5-3.5M10 13l3.5-3.5M4 16h12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-medium transition group-hover:text-brand-red">
                                        <?= esc($asset['label'] ?: lang('Learning.lesson.download')) ?>
                                    </span>
                                    <?php if ($bytes = $size($asset['size_bytes'] ?? 0)): ?>
                                        <span class="mt-0.5 block text-xs text-white/50 tabular-nums"><?= esc($bytes) ?></span>
                                    <?php endif; ?>
                                </span>
                                <span class="shrink-0 text-sm text-white/50"><?= esc(lang('Learning.lesson.download')) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php // ── The quiz ───────────────────────────────────────────────── ?>
        <?php if ($quiz !== null): ?>
            <section id="quiz" aria-labelledby="quiz-title" class="rounded-3xl border border-line bg-surface p-6 sm:p-8">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 id="quiz-title" class="text-xl font-bold sm:text-2xl">
                        <?= esc((int) $quiz['is_final'] === 1 ? lang('Learning.quiz.final') : lang('Learning.quiz.title')) ?>
                    </h2>
                    <span class="chip"><?= esc(lang('Learning.quiz.pass_mark', [(int) $quiz['pass_mark']])) ?></span>
                </div>

                <p class="mt-3 max-w-2xl text-sm text-white/70"><?= esc(lang('Learning.quiz.intro')) ?></p>

                <p class="mt-2 text-sm text-white/55">
                    <?php if ($attemptsLeft === null): ?>
                        <?= esc(lang('Learning.quiz.attempts_unlimited')) ?>
                    <?php elseif ($attemptsLeft === 1): ?>
                        <?= esc(lang('Learning.quiz.attempts_one')) ?>
                    <?php elseif ($attemptsLeft > 1): ?>
                        <?= esc(lang('Learning.quiz.attempts_left', [$attemptsLeft])) ?>
                    <?php endif; ?>
                    <?php if (! empty($quiz['time_limit_min'])): ?>
                        <?= esc(lang('Learning.quiz.time_limit', [(int) $quiz['time_limit_min']])) ?>
                    <?php endif; ?>
                </p>

                <?php // ── The result of the attempt just marked ──────────── ?>
                <?php if ($result !== null): ?>
                    <div role="status" class="mt-6 rounded-2xl border px-5 py-4 <?= $result['passed'] ? 'border-brand-red bg-brand-red/10' : 'border-line bg-brand-black/50' ?>">
                        <p class="font-semibold">
                            <?= esc($result['passed'] ? lang('Learning.quiz.result_passed') : lang('Learning.quiz.result_failed')) ?>
                        </p>
                        <p class="mt-1 text-sm text-white/70 tabular-nums">
                            <?= esc(lang('Learning.quiz.score', [(int) $result['percent'], (int) $result['score'], (int) $result['max']])) ?>
                        </p>
                        <?php if (! empty($result['certificate'])): ?>
                            <p class="mt-2 text-sm font-medium"><?= esc(lang('Learning.quiz.certificate_issued')) ?></p>
                        <?php endif; ?>
                        <?php if (! $result['passed'] && empty($result['unlocked'])): ?>
                            <?php // Why they can see a score and not the worked
                                  // answers. Without this sentence the
                                  // withholding reads as something broken. ?>
                            <p class="mt-2 text-sm text-white/60"><?= esc(lang('Learning.quiz.explanations_locked')) ?></p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($passedAlready): ?>
                    <p role="status" class="mt-6 rounded-2xl border border-brand-red bg-brand-red/10 px-5 py-4 font-semibold">
                        <?= esc(lang('Learning.quiz.result_passed')) ?>
                    </p>
                <?php endif; ?>

                <?php if ($questions === []): ?>
                    <p class="mt-6 text-white/60"><?= esc(lang('Learning.quiz.no_questions')) ?></p>

                <?php elseif (! $quizOpen && ! $passedAlready && $attemptsLeft === 0): ?>
                    <p class="mt-6 text-white/70"><?= esc(lang('Learning.quiz.attempts_none', [$attemptsAllowed])) ?></p>

                <?php elseif ($quizOpen): ?>
                    <form method="post" action="<?= esc($quizUrl, 'attr') ?>" class="mt-7 space-y-8">
                        <?= csrf_field() ?>

                        <?php foreach ($questions as $index => $question): ?>
                            <?php
                            $options = json_decode((string) $question['options_json'], true);
                            $options = is_array($options) ? $options : [];
                            $multiple = (string) $question['type'] === 'multiple';
                            $boolean  = (string) $question['type'] === 'boolean';
                            $qid      = (int) $question['id'];
                            $mark     = $marks[$qid] ?? null;

                            // A boolean question stores no options; the two are
                            // the same on every such question, so they are
                            // supplied here rather than duplicated into every
                            // row of the seed data.
                            if ($boolean && $options === []) {
                                $options = ['1' => lang('Learning.quiz.true'), '0' => lang('Learning.quiz.false')];
                            }
                            ?>
                            <fieldset>
                                <legend class="text-sm font-semibold uppercase tracking-[0.18em] text-white/50">
                                    <?= esc(lang('Learning.quiz.question', [$index + 1])) ?>
                                    <?php if ($mark !== null): ?>
                                        · <span class="<?= $mark['correct'] ? 'text-gold' : 'text-brand-red' ?>">
                                            <?= esc($mark['correct'] ? lang('Learning.quiz.correct') : lang('Learning.quiz.incorrect')) ?>
                                        </span>
                                    <?php endif; ?>
                                </legend>

                                <p class="mt-2 font-medium"><?= esc(t_field($question['prompt'])) ?></p>
                                <p class="mt-1 text-xs text-white/50">
                                    <?= esc($multiple ? lang('Learning.quiz.choose_all') : lang('Learning.quiz.choose_one')) ?>
                                </p>

                                <div class="mt-3 space-y-2">
                                    <?php foreach ($options as $value => $label): ?>
                                        <?php $id = 'q' . $qid . '-o' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value); ?>
                                        <label for="<?= esc($id, 'attr') ?>" class="flex cursor-pointer items-start gap-3 rounded-xl border border-line bg-brand-black/50 px-4 py-3 text-sm transition hover:border-brand-red">
                                            <input
                                                id="<?= esc($id, 'attr') ?>"
                                                type="<?= $multiple ? 'checkbox' : 'radio' ?>"
                                                name="answers[<?= $qid ?>]<?= $multiple ? '[]' : '' ?>"
                                                value="<?= esc((string) $value, 'attr') ?>"
                                                class="mt-0.5 h-4 w-4 shrink-0 accent-brand-red"
                                                <?php // `required` on a radio group is the browser's own
                                                      // "answer this one" and needs no script. A checkbox
                                                      // group cannot express "at least one" in HTML, so
                                                      // an unanswered multiple simply scores nothing. ?>
                                                <?= $multiple ? '' : 'required' ?>
                                            >
                                            <span><?= esc(is_array($label) ? t_field($label) : (string) $label) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($mark !== null && ! empty($mark['explanation'])): ?>
                                    <p class="mt-3 rounded-xl border border-line bg-brand-black/50 px-4 py-3 text-sm text-white/70">
                                        <span class="font-semibold text-white"><?= esc(lang('Learning.quiz.explanation')) ?>:</span>
                                        <?= esc($mark['explanation']) ?>
                                    </p>
                                <?php endif; ?>
                            </fieldset>
                        <?php endforeach; ?>

                        <button type="submit" class="btn-brand">
                            <?= esc($result === null ? lang('Learning.quiz.submit') : lang('Learning.quiz.try_again')) ?>
                        </button>
                    </form>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php // ── Previous and next ──────────────────────────────────────── ?>
        <nav class="flex flex-wrap items-stretch justify-between gap-4 border-t border-line pt-8" aria-label="<?= esc(lang('Learning.player.outline'), 'attr') ?>">
            <?php if ($previous !== null): ?>
                <a href="<?= esc($lessonUrl($previous)) ?>" class="group flex max-w-[45%] flex-col gap-1 text-left">
                    <span class="text-xs uppercase tracking-[0.18em] text-white/45"><?= esc(lang('Learning.lesson.previous')) ?></span>
                    <span class="font-medium transition group-hover:text-brand-red"><?= esc(t_field($previous['title'])) ?></span>
                </a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>

            <a href="<?= esc(locale_url('learn/' . $course['slug'])) ?>" class="self-center text-sm text-white/60 underline decoration-line underline-offset-4 transition hover:text-brand-red">
                <?= esc(lang('Learning.lesson.outline')) ?>
            </a>

            <?php if ($next !== null): ?>
                <a href="<?= esc($lessonUrl($next)) ?>" class="group flex max-w-[45%] flex-col gap-1 text-right">
                    <span class="text-xs uppercase tracking-[0.18em] text-white/45"><?= esc(lang('Learning.lesson.next')) ?></span>
                    <span class="font-medium transition group-hover:text-brand-red"><?= esc(t_field($next['title'])) ?></span>
                </a>
            <?php else: ?>
                <span></span>
            <?php endif; ?>
        </nav>
    </div>

    <?php // ── The outline, alongside ─────────────────────────────────────── ?>
    <aside class="lg:sticky lg:top-28 lg:self-start">
        <div class="rounded-3xl border border-line bg-surface p-5">
            <div class="flex items-baseline justify-between gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-white/50"><?= esc(lang('Learning.player.outline')) ?></h2>
                <?php // Bound to the component, so ticking a lesson off moves
                      // this number without a reload. ?>
                <p class="text-sm font-semibold tabular-nums" x-text="percent + '%'"><?= esc($percent . '%') ?></p>
            </div>

            <div class="mt-4 max-h-[60vh] space-y-5 overflow-y-auto pr-1">
                <?php foreach ($outline as $module): ?>
                    <?php if (($module['lessons'] ?? []) === []) { continue; } ?>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-[0.14em] text-white/40">
                            <?= esc(t_field($module['title'] ?? '') ?: lang('Learning.player.module_untitled')) ?>
                        </h3>
                        <ol class="mt-2 space-y-1">
                            <?php foreach ($module['lessons'] as $row): ?>
                                <?php $isCurrent = (int) $row['id'] === (int) $lesson['id']; ?>
                                <li>
                                    <a href="<?= esc($lessonUrl($row)) ?>"
                                       class="flex items-start gap-2.5 rounded-lg px-2 py-1.5 text-sm transition hover:bg-brand-red/5 <?= $isCurrent ? 'bg-brand-red/10 font-semibold' : 'text-white/70' ?>"
                                       <?= $isCurrent ? 'aria-current="true"' : '' ?>>
                                        <?php if ($row['state'] === 'completed'): ?>
                                            <svg class="mt-1 h-3.5 w-3.5 shrink-0 text-brand-red" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M4 10.5l4 4 8-9" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <?php elseif ($row['state'] === 'started'): ?>
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-gold" aria-hidden="true"></span>
                                        <?php else: ?>
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full border border-line" aria-hidden="true"></span>
                                        <?php endif; ?>
                                        <span class="min-w-0">
                                            <?= esc(t_field($row['title'])) ?>
                                            <?php // The state in words, off-screen, because the tick
                                                  // and the dot say nothing to a screen reader and
                                                  // nothing to anybody who cannot tell the two
                                                  // colours apart. ?>
                                            <span class="sr-only">
                                                — <?= esc(match ((string) $row['state']) {
                                                    'completed' => lang('Learning.player.status_completed'),
                                                    'started'   => lang('Learning.player.status_started'),
                                                    default     => lang('Learning.player.status_todo'),
                                                }) ?>
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endforeach; ?>
            </div>

            <a href="<?= esc(locale_url('learn/' . $course['slug'])) ?>" class="mt-5 block border-t border-line pt-4 text-sm text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Learning.lesson.outline')) ?>
            </a>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
/**
 * The lesson player's Alpine component.
 *
 * A classic script at the foot of the body, so it is defined before Alpine's
 * own module runs — module scripts are deferred and execute after the document
 * has been parsed, which is after this. A global factory rather than
 * Alpine.data(), because registration has to happen before Alpine.start() and
 * that call lives in resources/js/app.js, which belongs to another agent.
 *
 * What it is for: a learner who closes the tab half way through a lesson should
 * open it tomorrow where they stopped. That means reporting a position often
 * enough to be useful and rarely enough not to flood the busiest table in the
 * schema — hence a fifteen-second timer that only runs while the video is
 * playing, a ten-second floor on how small a change is worth sending, and a
 * server-side ceiling on top of both.
 */
window.lessonPlayer = function () {
    // JSON_HEX_TAG escapes `<` and `>` to \u003C and \u003E, which is what
    // stops a stored value holding a closing script tag from ending this block
    // early. They are ordinary escapes inside a JavaScript string, so nothing
    // that reads the config sees any difference.
    const config = <?= json_encode([
        'endpoint'  => $progressUrl,
        'tokenName' => csrf_token(),
        'token'     => csrf_hash(),
        'position'  => (int) $startAt,
        'percent'   => (int) $percent,
        'completed' => $completed,
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    return {
        endpoint: config.endpoint,
        tokenName: config.tokenName,
        token: config.token,
        position: config.position,
        percent: config.percent,
        completed: config.completed,

        saving: false,
        failed: false,
        lastSent: config.position,
        timer: null,

        init() {
            const video = this.$refs.video;

            if (video) {
                if (this.position > 0) {
                    // Seeking before the browser knows the duration is ignored
                    // without an error on several engines, so the seek waits for
                    // metadata unless it has already arrived.
                    const seek = () => { try { video.currentTime = this.position; } catch (e) {} };
                    video.readyState > 0 ? seek() : video.addEventListener('loadedmetadata', seek, { once: true });
                }

                video.addEventListener('play', () => this.startTimer());
                video.addEventListener('pause', () => { this.stopTimer(); this.send(false); });
                video.addEventListener('ended', () => { this.stopTimer(); this.send(true); });
            }

            // Switching tabs is the commonest way a position is lost, and the
            // page is still alive afterwards — so this is an ordinary request
            // that brings a fresh CSRF token back with it.
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.send(false);
            });

            // Leaving for good. A fetch started here is cancelled with the page,
            // so the last position goes by beacon — but only when the page is
            // NOT being kept for the back button. A beacon spends the CSRF token
            // without returning its replacement, and a page restored from the
            // back-forward cache would come back holding a dead one.
            window.addEventListener('pagehide', (event) => {
                if (! event.persisted) this.beacon();
            });
        },

        /** Where the video is now, or the last known position when there is none. */
        at() {
            const video = this.$refs.video;

            return video && Number.isFinite(video.currentTime)
                ? Math.floor(video.currentTime)
                : this.position;
        },

        startTimer() {
            if (! this.timer) this.timer = setInterval(() => this.send(false), 15000);
        },

        stopTimer() {
            if (this.timer) { clearInterval(this.timer); this.timer = null; }
        },

        /**
         * Take the token the server just issued.
         *
         * Config\Security::$regenerate is on, so every accepted POST invalidates
         * the token sitting in the forms on this page. Without this, a learner
         * who watched for five minutes and then submitted the quiz would be
         * refused, and the refusal would look like a bug in the quiz.
         */
        adopt(token) {
            if (! token) return;

            this.token = token;
            document
                .querySelectorAll('input[name=' + JSON.stringify(this.tokenName) + ']')
                .forEach((field) => { field.value = token; });
        },

        async send(complete) {
            if (this.saving) return;

            const at = this.at();
            // A heartbeat that has not moved is a write for nothing. The
            // deliberate click is never held back by this.
            if (! complete && at - this.lastSent < 10) return;

            this.saving = true;

            try {
                const body = new FormData();
                body.append(this.tokenName, this.token);
                body.append('position', at);
                if (complete) body.append('complete', '1');

                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    body: body,
                    credentials: 'same-origin',
                    keepalive: true,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });

                const type = response.headers.get('content-type') || '';
                const data = type.indexOf('json') !== -1 ? await response.json() : {};
                this.adopt(data.csrf);

                if (response.ok && data.ok) {
                    this.lastSent = at;
                    this.position = at;
                    this.failed = false;
                    if (typeof data.percent === 'number') this.percent = data.percent;
                    if (data.completed) this.completed = true;
                } else if (response.status !== 429) {
                    // 429 is the server saying "too soon", which is not a failure
                    // and none of the learner's business. Anything else is, and
                    // the timer stops rather than hammering a refusal for an hour.
                    this.failed = true;
                    this.stopTimer();
                }
            } catch (e) {
                this.failed = true;
                this.stopTimer();
            } finally {
                this.saving = false;
            }
        },

        beacon() {
            const at = this.at();
            if (! navigator.sendBeacon || at - this.lastSent < 5) return;

            const body = new FormData();
            body.append(this.tokenName, this.token);
            body.append('position', at);

            try { navigator.sendBeacon(this.endpoint, body); this.lastSent = at; } catch (e) {}
        }
    };
};
</script>
<?= $this->endSection() ?>
