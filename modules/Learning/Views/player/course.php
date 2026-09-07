<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The course outline, for somebody who has already paid for it.
 *
 * This is not the sales page and it does not try to be one. The buyer has
 * bought; what they need is to know where they are, what is left, and one
 * button that takes them to the next thing without having to remember which
 * lesson that was. So the page is a list with a state against each row and a
 * panel that answers "where was I".
 *
 * The resume button points at the first lesson in course order that is not
 * complete — see Player::course() for why the first-incomplete rule beats
 * last-touched. When everything is complete it points at the last lesson and
 * says "review", because a course that finishes by removing its own button
 * reads as a course that has locked itself.
 *
 * Each row carries a ring rather than a percentage. The ring is decorative and
 * marked as such; the state is in words in the same row, because "a circle that
 * is about three quarters filled" is not something a screen reader can say and
 * not something a colour-blind reader can distinguish from an empty one.
 *
 * @var array       $course
 * @var list<array> $outline  modules, each with `lessons` carrying state/position/files
 * @var int         $total
 * @var int         $done
 * @var int         $percent
 * @var bool        $started
 * @var array|null  $resume
 * @var array|null  $certificate
 * @var bool        $hasFinal
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

/** A running time in the shape a lesson needs: minutes, or hours and minutes. */
$clock = static function (int $seconds): string {
    if ($seconds <= 0) {
        return '';
    }
    $minutes = max(1, (int) round($seconds / 60));

    return $minutes < 60
        ? lang('Learning.clock.min', [$minutes])
        : lang('Learning.clock.hour', [intdiv($minutes, 60), str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT)]);
};

/**
 * One progress ring, as inline SVG.
 *
 * r = 8 puts the circumference at 50.27, which is the number the dash offset is
 * a fraction of; changing the radius without changing that constant produces a
 * ring that is quietly always full.
 */
$ring = static function (int $percent, bool $complete): string {
    $circumference = 50.27;
    $offset        = $complete ? 0.0 : $circumference * (1 - max(0, min(100, $percent)) / 100);
    $colour        = $complete ? 'text-brand-red' : 'text-gold';

    return '<svg class="h-5 w-5 shrink-0 -rotate-90 ' . $colour . '" viewBox="0 0 20 20" aria-hidden="true">'
        . '<circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2.5" class="text-line" opacity="0.35"/>'
        . '<circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"'
        . ' stroke-dasharray="' . $circumference . '" stroke-dashoffset="' . round($offset, 2) . '"/>'
        . '</svg>';
};

$stateLabel = static fn (string $state): string => match ($state) {
    'completed' => lang('Learning.player.status_completed'),
    'started'   => lang('Learning.player.status_started'),
    default     => lang('Learning.player.status_todo'),
};

$resumeLabel = $total > 0 && $done >= $total
    ? lang('Learning.player.review')
    : ($started ? lang('Learning.player.resume') : lang('Learning.player.start'));
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Learning.player.eyebrow'),
    'heading' => t_field($course['title']),
    'intro'   => t_field($course['summary']),
], ['saveData' => false]) ?>

<div class="container-x grid gap-10 py-14 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-14">

    <div class="min-w-0 space-y-10">

        <?php if ($msg = session()->getFlashdata('notice')): ?>
            <p role="status" class="rounded-xl border border-gold bg-gold/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>

        <?php if ($total === 0): ?>

            <?php // A course somebody has paid for with nothing recorded on it
                  // yet. Said out loud, with the reassurance that matters — the
                  // booking is not lost — because a blank page here looks
                  // exactly like a purchase that went nowhere. ?>
            <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
                <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Learning.player.empty_heading')) ?></h2>
                <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Learning.player.empty_body')) ?></p>
                <div class="mt-6 flex flex-wrap gap-4">
                    <a href="<?= esc(locale_url('account/courses')) ?>" class="btn-brand"><?= esc(lang('Learning.player.empty_cta')) ?></a>
                    <a href="<?= esc(course_url((string) $course['slug'])) ?>" class="btn-ghost"><?= esc(lang('Learning.player.course_page')) ?></a>
                </div>
            </section>

        <?php else: ?>

            <section aria-labelledby="outline">
                <h2 id="outline" class="section-title"><?= esc(lang('Learning.player.outline')) ?></h2>

                <div class="mt-5 space-y-8">
                    <?php foreach ($outline as $module): ?>
                        <?php if (($module['lessons'] ?? []) === []) { continue; } ?>

                        <div>
                            <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-white/50">
                                <?= esc(t_field($module['title'] ?? '') ?: lang('Learning.player.module_untitled')) ?>
                            </h3>

                            <?php if ($summary = t_field($module['summary'] ?? '')): ?>
                                <p class="mt-2 max-w-2xl text-sm text-white/60"><?= esc($summary) ?></p>
                            <?php endif; ?>

                            <ol class="mt-4 divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                                <?php foreach ($module['lessons'] as $lesson): ?>
                                    <?php
                                    $duration = (int) $lesson['duration_sec'];
                                    $complete = $lesson['state'] === 'completed';
                                    // The ring reads position against duration.
                                    // With no duration recorded there is nothing
                                    // to be a fraction of, so a started lesson
                                    // shows a fixed quarter rather than a ring
                                    // that would always be empty.
                                    $fraction = $complete
                                        ? 100
                                        : ($lesson['state'] === 'started'
                                            ? ($duration > 0 ? (int) round(min(1, $lesson['position'] / $duration) * 100) : 25)
                                            : 0);
                                    $href = locale_url('learn/' . $course['slug'] . '/' . $lesson['slug']);
                                    ?>
                                    <li>
                                        <a href="<?= esc($href) ?>" class="group flex items-start gap-4 px-5 py-4 transition hover:bg-brand-red/5 focus-visible:bg-brand-red/5">
                                            <span class="mt-0.5"><?= $ring($fraction, $complete) ?></span>

                                            <span class="min-w-0 flex-1">
                                                <span class="block font-medium transition group-hover:text-brand-red">
                                                    <?= esc(t_field($lesson['title'])) ?>
                                                </span>

                                                <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-white/55">
                                                    <span><?= esc($stateLabel((string) $lesson['state'])) ?></span>
                                                    <?php if ($duration > 0): ?>
                                                        <span aria-hidden="true">·</span>
                                                        <span class="tabular-nums"><?= esc($clock($duration)) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ((int) $lesson['files'] > 0): ?>
                                                        <span aria-hidden="true">·</span>
                                                        <span><?= esc((int) $lesson['files'] === 1
                                                            ? lang('Learning.player.badge_file')
                                                            : lang('Learning.player.badge_files', [(int) $lesson['files']])) ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </span>

                                            <span class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                                                <?php if (! empty($lesson['is_preview'])): ?>
                                                    <span class="chip"><?= esc(lang('Learning.player.badge_preview')) ?></span>
                                                <?php endif; ?>
                                                <?php if (! empty($lesson['is_final'])): ?>
                                                    <span class="chip chip-accent"><?= esc(lang('Learning.player.badge_final')) ?></span>
                                                <?php elseif (! empty($lesson['has_quiz'])): ?>
                                                    <span class="chip"><?= esc(lang('Learning.player.badge_quiz')) ?></span>
                                                <?php endif; ?>
                                            </span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        <?php endif; ?>
    </div>

    <?php // ── Where you are, and the one button ──────────────────────────── ?>
    <aside class="lg:sticky lg:top-28 lg:self-start">
        <div class="rounded-3xl border border-line bg-surface p-6">

            <?php // The big ring is decorative; the same number is spelled out
                  // under it, and role="status" is not used because this is not
                  // an update — it is simply what the page says. ?>
            <div class="flex items-center gap-4">
                <svg class="h-16 w-16 shrink-0 -rotate-90" viewBox="0 0 40 40" aria-hidden="true">
                    <circle cx="20" cy="20" r="17" fill="none" stroke="currentColor" stroke-width="4" class="text-line" opacity="0.35"/>
                    <circle cx="20" cy="20" r="17" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"
                            class="text-brand-red"
                            stroke-dasharray="106.81"
                            stroke-dashoffset="<?= esc((string) round(106.81 * (1 - max(0, min(100, $percent)) / 100), 2), 'attr') ?>"/>
                </svg>
                <div class="min-w-0">
                    <p class="text-2xl font-bold tabular-nums"><?= esc(lang('Learning.player.percent', [$percent])) ?></p>
                    <p class="mt-1 text-sm text-white/60">
                        <?= $total === 0
                            ? esc(lang('Learning.player.not_started'))
                            : esc(lang('Learning.player.lessons_done', [$done, $total])) ?>
                    </p>
                </div>
            </div>

            <?php if ($resume !== null): ?>
                <a href="<?= esc(locale_url('learn/' . $course['slug'] . '/' . $resume['slug'])) ?>" class="btn-brand mt-6 w-full">
                    <?= esc($resumeLabel) ?>
                </a>
                <p class="mt-2 text-center text-xs text-white/55"><?= esc(t_field($resume['title'])) ?></p>
            <?php endif; ?>

            <?php // ── The certificate ────────────────────────────────────── ?>
            <div class="mt-6 border-t border-line pt-6">
                <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-white/50">
                    <?= esc(lang('Learning.player.certificate_heading')) ?>
                </h2>

                <?php if ($certificate !== null && empty($certificate['revoked_at'])): ?>
                    <p class="mt-3 text-sm text-white/70"><?= esc(lang('Learning.player.certificate_ready')) ?></p>
                    <a href="<?= esc(locale_url('account/certificates/' . (int) $certificate['id'] . '/download')) ?>"
                       class="btn-ghost mt-4 w-full">
                        <?= esc(lang('Learning.player.certificate_view')) ?>
                    </a>
                <?php elseif ($certificate !== null): ?>
                    <?php // Withdrawn certificates keep their record and keep
                          // verifying; what they do not do is keep offering a
                          // download that looks valid. ?>
                    <p class="mt-3 text-sm text-white/70"><?= esc(lang('Learning.certificate.revoked')) ?></p>
                <?php else: ?>
                    <p class="mt-3 text-sm text-white/70">
                        <?= esc($hasFinal
                            ? lang('Learning.player.certificate_pending')
                            : lang('Learning.player.certificate_no_final')) ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="mt-6 flex flex-col gap-2 border-t border-line pt-6 text-sm">
                <a href="<?= esc(course_url((string) $course['slug'])) ?>" class="text-brand-red underline decoration-line underline-offset-4">
                    <?= esc(lang('Learning.player.course_page')) ?>
                </a>
                <a href="<?= esc(locale_url('account/courses')) ?>" class="text-white/60 underline decoration-line underline-offset-4 transition hover:text-brand-red">
                    <?= esc(lang('Learning.player.back_to_courses')) ?>
                </a>
            </div>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
