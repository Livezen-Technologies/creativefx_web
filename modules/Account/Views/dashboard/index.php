<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The account overview.
 *
 * Three questions, in the order a learner actually has them: am I meant to be
 * somewhere soon, where had I got to, and is anything wrong. Everything else
 * lives one click away in the sidebar, because a dashboard that shows all of
 * somebody's history at once is a dashboard nobody reads the top of.
 *
 * "Needs attention" is built only from facts already in the database — an
 * unverified address, an order still waiting for money, a class the school
 * called off, a transfer nobody has decided. Nothing on it is a nudge or an
 * upsell. A panel that manufactures tasks teaches people to ignore the panel,
 * and then the one that matters is ignored too.
 *
 * The empty state routes into the catalogue rather than apologising. A new
 * account with nothing in it is the normal first thing that happens after
 * registering, not a failure to explain.
 *
 * @var array|null  $user
 * @var list<array> $upcoming    active enrolments with a start date ahead of today
 * @var list<array> $selfPaced   enrolments with no dates, each carrying `percent`
 * @var array{unverified:bool, unpaid:list<array>, cancelled:list<array>, transfers:list<array>} $attention
 * @var string      $current
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

$firstName = trim((string) ($user['first_name'] ?? ''));
$hasAny    = $upcoming !== [] || $selfPaced !== [];
$needsWork = $attention['unverified']
    || $attention['unpaid'] !== []
    || $attention['cancelled'] !== []
    || $attention['transfers'] !== [];
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Account.nav.title'),
    'heading' => $firstName !== ''
        ? lang('Account.dashboard.greeting_named', [$firstName])
        : lang('Account.dashboard.greeting'),
    'intro'   => lang('Account.dashboard.intro'),
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

        <?php // ── Needs attention ────────────────────────────────────────── ?>
        <?php if ($needsWork): ?>
            <section aria-labelledby="attention" class="rounded-3xl border border-gold bg-surface p-6 sm:p-7">
                <h2 id="attention" class="text-lg font-bold"><?= esc(lang('Account.dashboard.attention')) ?></h2>

                <ul class="mt-4 space-y-4">
                    <?php if ($attention['unverified']): ?>
                        <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <?php // Not a wall. An unverified learner can still
                                  // reach their joining link on the morning of
                                  // the class — see LearnerAuth::attempt() —
                                  // so this asks rather than blocks. ?>
                            <span class="text-white/80"><?= esc(lang('Account.dashboard.unverified')) ?></span>
                            <a href="<?= esc(locale_url('account/profile')) ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                                <?= esc(lang('Account.dashboard.unverified_action')) ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php foreach ($attention['unpaid'] as $order): ?>
                        <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <span class="text-white/80">
                                <?= esc(lang('Account.dashboard.unpaid', [
                                    $order['order_no'],
                                    money((int) $order['total_cents'], (string) $order['currency']),
                                ])) ?>
                            </span>
                            <a href="<?= esc(locale_url('order/' . $order['order_no'])) ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                                <?= esc(lang('Account.dashboard.unpaid_action')) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>

                    <?php foreach ($attention['cancelled'] as $enrolment): ?>
                        <li class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <?php // Said plainly and early. A learner who finds
                                  // out on the morning that the class was called
                                  // off three weeks ago has been let down twice. ?>
                            <span class="text-white/80">
                                <?= esc(lang('Account.dashboard.class_cancelled', [
                                    t_field($enrolment['course_title']),
                                    session_dates($enrolment),
                                ])) ?>
                            </span>
                            <?php if (! empty($enrolment['session_id'])): ?>
                                <a href="<?= esc(locale_url('account/live/' . (int) $enrolment['session_id'])) ?>" class="text-sm font-medium text-brand-red underline decoration-line underline-offset-4">
                                    <?= esc(lang('Account.dashboard.class_cancelled_action')) ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>

                    <?php foreach ($attention['transfers'] as $transfer): ?>
                        <li class="text-white/80">
                            <?= esc(lang('Account.dashboard.transfer_pending', [t_field($transfer['course_title'])])) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php // ── Coming up ──────────────────────────────────────────────── ?>
        <section aria-labelledby="upcoming">
            <h2 id="upcoming" class="section-title"><?= esc(lang('Account.dashboard.upcoming')) ?></h2>

            <?php if ($upcoming === []): ?>
                <p class="mt-4 max-w-2xl text-white/60"><?= esc(lang('Account.dashboard.upcoming_none')) ?></p>
            <?php else: ?>
                <ul class="mt-5 space-y-4">
                    <?php foreach ($upcoming as $enrolment): ?>
                        <li class="rounded-2xl border border-line bg-surface p-5 sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-brand-red">
                                        <?= esc(mode_label((string) $enrolment['mode'])) ?>
                                    </p>
                                    <h3 class="mt-1 text-lg font-semibold"><?= esc(t_field($enrolment['course_title'])) ?></h3>

                                    <p class="mt-2 text-sm text-white/70">
                                        <span class="font-medium text-white"><?= esc(session_dates($enrolment)) ?></span>
                                        <?php if ($times = session_times($enrolment)): ?>
                                            · <?= esc($times) ?>
                                        <?php endif; ?>
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
            <?php endif; ?>
        </section>

        <?php // ── In progress ────────────────────────────────────────────── ?>
        <?php if ($selfPaced !== []): ?>
            <section aria-labelledby="in-progress">
                <h2 id="in-progress" class="section-title"><?= esc(lang('Account.dashboard.in_progress')) ?></h2>

                <ul class="mt-5 grid gap-4 sm:grid-cols-2">
                    <?php foreach ($selfPaced as $enrolment): ?>
                        <?php $percent = (int) ($enrolment['percent'] ?? 0); ?>
                        <li class="rounded-2xl border border-line bg-surface p-5">
                            <h3 class="font-semibold"><?= esc(t_field($enrolment['course_title'])) ?></h3>

                            <?php // role="progressbar" with the three value
                                  // attributes, because a bar drawn as a div is
                                  // silent otherwise. The percentage is also
                                  // written out below it: colour and width are
                                  // not information anybody can rely on. ?>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10"
                                 role="progressbar"
                                 aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"
                                 aria-label="<?= esc(lang('Account.dashboard.progress_label', [t_field($enrolment['course_title'])]), 'attr') ?>">
                                <div class="h-full rounded-full bg-brand-red" style="width:<?= $percent ?>%"></div>
                            </div>

                            <p class="mt-2 text-sm text-white/60"><?= esc(lang('Account.dashboard.percent_complete', [$percent])) ?></p>

                            <a href="<?= esc(locale_url('learn/' . $enrolment['course_slug'])) ?>" class="btn-ghost mt-4">
                                <?= esc($percent > 0 ? lang('Account.dashboard.resume') : lang('Account.dashboard.start')) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <?php // ── Nothing here yet ───────────────────────────────────────── ?>
        <?php if (! $hasAny): ?>
            <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
                <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Account.dashboard.empty_heading')) ?></h2>
                <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Account.dashboard.empty_body')) ?></p>
                <div class="mt-6 flex flex-wrap gap-4">
                    <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand"><?= esc(lang('Account.dashboard.browse_courses')) ?></a>
                    <a href="<?= esc(locale_url('schedule')) ?>" class="btn-ghost"><?= esc(lang('Account.dashboard.see_dates')) ?></a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
