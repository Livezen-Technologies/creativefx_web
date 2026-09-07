<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * Every enrolment this learner holds, in three groups.
 *
 * The grouping is the page. "Coming up" is what somebody has to act on;
 * self-paced study is available now, until its licence runs out; and
 * everything past stays visible because a course finished last March is still
 * where the recording, the materials and the certificate live. A list that
 * quietly drops completed classes is a list that generates support email.
 *
 * A cancelled or transferred booking stays here too, marked as what it is. A
 * booking that disappears looks, to the person who made it, exactly like a
 * booking that was lost.
 *
 * Two destinations, decided by whether the enrolment has dates: a dated class
 * goes to its joining page, self-paced study goes to the player.
 *
 * @var list<array> $upcoming
 * @var list<array> $selfPaced  each carrying `percent`
 * @var list<array> $past
 * @var string      $current
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

/** The chip for an enrolment whose status is not simply "booked". */
$statusChip = static function (array $enrolment): string {
    return match ((string) $enrolment['status']) {
        'completed'   => lang('Account.status.completed'),
        'cancelled'   => lang('Account.status.cancelled'),
        'transferred' => lang('Account.status.transferred'),
        default       => '',
    };
};
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Account.nav.title'),
    'heading' => lang('Account.courses.title'),
    'intro'   => lang('Account.courses.intro'),
], ['saveData' => false]) ?>

<div class="container-x grid gap-10 py-14 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-14">

    <?= view('Modules\Account\Views\partials\account_nav', ['current' => $current], ['saveData' => false]) ?>

    <div class="min-w-0 space-y-12">

        <?php if ($msg = session()->getFlashdata('notice')): ?>
            <p role="status" class="rounded-xl border border-gold bg-gold/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>

        <?php if ($upcoming === [] && $selfPaced === [] && $past === []): ?>

            <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
                <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Account.courses.empty_heading')) ?></h2>
                <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Account.courses.empty_body')) ?></p>
                <div class="mt-6 flex flex-wrap gap-4">
                    <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand"><?= esc(lang('Account.dashboard.browse_courses')) ?></a>
                    <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost"><?= esc(lang('Account.dashboard.see_dates')) ?></a>
                </div>
            </section>

        <?php else: ?>

            <?php // ── Coming up ──────────────────────────────────────────── ?>
            <?php if ($upcoming !== []): ?>
                <section aria-labelledby="upcoming">
                    <h2 id="upcoming" class="section-title"><?= esc(lang('Account.dashboard.upcoming')) ?></h2>

                    <ul class="mt-5 space-y-4">
                        <?php foreach ($upcoming as $enrolment): ?>
                            <li class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="chip"><?= esc(mode_label((string) $enrolment['mode'])) ?></span>
                                            <?php if ($chip = $statusChip($enrolment)): ?>
                                                <span class="chip"><?= esc($chip) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <h3 class="mt-3 text-lg font-semibold">
                                            <a href="<?= esc(course_url((string) $enrolment['course_slug'])) ?>" class="transition hover:text-brand-red">
                                                <?= esc(t_field($enrolment['course_title'])) ?>
                                            </a>
                                        </h3>

                                        <p class="mt-2 text-sm text-white/70">
                                            <span class="font-medium text-white"><?= esc(session_dates($enrolment)) ?></span>
                                            <?php if ($times = session_times($enrolment)): ?> · <?= esc($times) ?><?php endif; ?>
                                            <?php if (! empty($enrolment['venue_name'])): ?>
                                                · <?= esc($enrolment['venue_name']) ?><?php if (! empty($enrolment['venue_city'])): ?>, <?= esc($enrolment['venue_city']) ?><?php endif; ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <?php if (! empty($enrolment['session_id'])): ?>
                                        <a href="<?= esc(locale_url('account/live/' . (int) $enrolment['session_id'])) ?>" class="btn-brand shrink-0">
                                            <?= esc(lang('Account.dashboard.joining_details')) ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php // ── Self-paced ─────────────────────────────────────────── ?>
            <?php if ($selfPaced !== []): ?>
                <section aria-labelledby="self-paced">
                    <h2 id="self-paced" class="section-title"><?= esc(lang('Account.courses.self_paced')) ?></h2>

                    <ul class="mt-5 grid gap-4 sm:grid-cols-2">
                        <?php foreach ($selfPaced as $enrolment): ?>
                            <?php $percent = (int) ($enrolment['percent'] ?? 0); ?>
                            <li class="rounded-2xl border border-line bg-surface p-5">
                                <h3 class="font-semibold"><?= esc(t_field($enrolment['course_title'])) ?></h3>

                                <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10"
                                     role="progressbar"
                                     aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"
                                     aria-label="<?= esc(lang('Account.dashboard.progress_label', [t_field($enrolment['course_title'])]), 'attr') ?>">
                                    <div class="h-full rounded-full bg-brand-red" style="width:<?= $percent ?>%"></div>
                                </div>

                                <p class="mt-2 text-sm text-white/60"><?= esc(lang('Account.dashboard.percent_complete', [$percent])) ?></p>

                                <?php
                                // The twelve-month licence, counted down rather
                                // than merely dated: a learner who reads "access
                                // until 7 September" in August has been told the
                                // fact but not the urgency, and the last month
                                // is when somebody actually opens the course
                                // they meant to finish.
                                $ends = $enrolment['expires_at'] ?? null;
                                if ($ends !== null):
                                    $days = (int) floor((strtotime((string) $ends) - time()) / 86400);
                                    $when = date('j M Y', strtotime((string) $ends));
                                    if ($days < 0) {
                                        $line = lang('Account.courses.access_ended', [$when]);
                                        $tone = 'text-brand-red';
                                    } elseif ($days <= 30) {
                                        $line = lang('Account.courses.access_soon', [$when, $days]);
                                        $tone = 'text-gold';
                                    } else {
                                        $line = lang('Account.courses.access_until', [$when]);
                                        $tone = 'text-white/45';
                                    }
                                    ?>
                                    <p class="mt-1 text-xs <?= esc($tone, 'attr') ?>"><?= esc($line) ?></p>
                                <?php endif; ?>

                                <a href="<?= esc(locale_url('learn/' . $enrolment['course_slug'])) ?>" class="btn-ghost mt-4">
                                    <?= esc($percent > 0 ? lang('Account.dashboard.resume') : lang('Account.dashboard.start')) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>

            <?php // ── Already happened ───────────────────────────────────── ?>
            <?php if ($past !== []): ?>
                <section aria-labelledby="past">
                    <h2 id="past" class="section-title"><?= esc(lang('Account.courses.past')) ?></h2>
                    <p class="mt-2 text-sm text-white/55"><?= esc(lang('Account.courses.past_note')) ?></p>

                    <?php // A table, because these rows are being compared —
                          // which course, when, what happened — and a stack of
                          // cards makes that comparison harder for no gain. It
                          // scrolls inside its own box on a narrow screen so the
                          // page itself never scrolls sideways. ?>
                    <div class="relative mt-5 overflow-x-auto rounded-2xl border border-line">
                        <table class="w-full min-w-[34rem] border-collapse text-sm">
                            <caption class="sr-only"><?= esc(lang('Account.courses.past_caption')) ?></caption>
                            <thead>
                                <tr class="border-b border-line bg-surface text-left text-xs uppercase tracking-wider text-white/55">
                                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Account.courses.col_course')) ?></th>
                                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Account.courses.col_when')) ?></th>
                                    <th scope="col" class="px-4 py-3 font-semibold"><?= esc(lang('Account.courses.col_status')) ?></th>
                                    <th scope="col" class="px-4 py-3 font-semibold"><span class="sr-only"><?= esc(lang('Account.courses.col_actions')) ?></span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                <?php foreach ($past as $enrolment): ?>
                                    <tr>
                                        <td class="px-4 py-3">
                                            <a href="<?= esc(course_url((string) $enrolment['course_slug'])) ?>" class="font-medium transition hover:text-brand-red">
                                                <?= esc(t_field($enrolment['course_title'])) ?>
                                            </a>
                                            <span class="block text-xs text-white/50"><?= esc(mode_label((string) $enrolment['mode'])) ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-white/70"><?= esc(session_dates($enrolment)) ?></td>
                                        <td class="px-4 py-3">
                                            <?php // "Attended" would be a claim
                                                  // about a register that may not
                                                  // have been marked. The truthful
                                                  // label for an enrolment still
                                                  // open on a date that has gone
                                                  // is that the date has gone. ?>
                                            <?= esc($statusChip($enrolment) ?: lang('Account.status.past')) ?>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <?php if (! empty($enrolment['session_id'])): ?>
                                                <a href="<?= esc(locale_url('account/live/' . (int) $enrolment['session_id'])) ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4">
                                                    <?= esc(lang('Account.courses.open')) ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
