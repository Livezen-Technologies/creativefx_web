<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The learner's certificates, and the two links that matter for each one.
 *
 * **Download** streams the PDF through the controller, which checks who is
 * asking. The file itself lives under `writable/` and has no address of its
 * own: it carries somebody's full name and what they studied, and served from
 * the web root it would be one guessed filename away from anybody.
 *
 * **Verify** is the public page, at an address with no locale prefix, that an
 * HR department or a visa officer can open without ever having been to this
 * site. It is the whole reason a certificate is worth issuing — a PDF is a
 * picture and anybody can make one that says anything — so it is offered here
 * rather than hidden, and the learner is told they can send it to anybody.
 *
 * A revoked certificate stays on this page, marked as revoked and with no
 * download. The record does not disappear, because `/verify/{code}` has to keep
 * answering; but a document that has been withdrawn is not handed back out
 * looking valid.
 *
 * @var list<array> $certificates
 * @var string      $current
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);
?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'crumbs'  => $crumbs,
    'eyebrow' => lang('Account.nav.title'),
    'heading' => lang('Account.certificates.title'),
    'intro'   => lang('Account.certificates.intro'),
], ['saveData' => false]) ?>

<div class="container-x grid gap-10 py-14 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-14">

    <?= view('Modules\Account\Views\partials\account_nav', ['current' => $current], ['saveData' => false]) ?>

    <div class="min-w-0 space-y-8">

        <?php if ($msg = session()->getFlashdata('notice')): ?>
            <p role="status" class="rounded-xl border border-gold bg-gold/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>
        <?php if ($msg = session()->getFlashdata('error')): ?>
            <p role="alert" class="rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
        <?php endif; ?>

        <?php if ($certificates === []): ?>

            <?php // Honest about what has to happen first. A page that simply
                  // says "no certificates" leaves somebody who finished a class
                  // last week wondering whether something went wrong. ?>
            <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
                <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Account.certificates.empty_heading')) ?></h2>
                <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Account.certificates.empty_body')) ?></p>
                <a href="<?= esc(locale_url('account/courses')) ?>" class="btn-ghost mt-6">
                    <?= esc(lang('Account.certificates.empty_action')) ?>
                </a>
            </section>

        <?php else: ?>

            <ul class="space-y-4">
                <?php foreach ($certificates as $certificate): ?>
                    <?php $revoked = ! empty($certificate['revoked_at']); ?>
                    <li class="rounded-2xl border <?= $revoked ? 'border-brand-red' : 'border-line' ?> bg-surface p-5 sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-5">
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold"><?= esc($certificate['title']) ?></h2>

                                <dl class="mt-3 grid gap-x-8 gap-y-1 text-sm sm:grid-cols-2">
                                    <div class="flex gap-2">
                                        <dt class="text-white/50"><?= esc(lang('Account.certificates.issued')) ?></dt>
                                        <dd><?= esc(empty($certificate['issued_at']) ? '—' : date('j M Y', strtotime((string) $certificate['issued_at']))) ?></dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="text-white/50"><?= esc(lang('Account.certificates.serial')) ?></dt>
                                        <dd class="font-mono text-white/80"><?= esc($certificate['serial']) ?></dd>
                                    </div>
                                    <?php if ((int) $certificate['hours'] > 0): ?>
                                        <div class="flex gap-2">
                                            <dt class="text-white/50"><?= esc(lang('Account.certificates.hours')) ?></dt>
                                            <dd><?= (int) $certificate['hours'] ?></dd>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (! empty($certificate['mode'])): ?>
                                        <div class="flex gap-2">
                                            <dt class="text-white/50"><?= esc(lang('Account.certificates.mode')) ?></dt>
                                            <dd><?= esc(mode_label((string) $certificate['mode'])) ?></dd>
                                        </div>
                                    <?php endif; ?>
                                </dl>

                                <?php if ($revoked): ?>
                                    <p role="status" class="mt-4 text-sm font-medium text-brand-red">
                                        <?= esc(lang('Account.certificates.revoked', [date('j M Y', strtotime((string) $certificate['revoked_at']))])) ?>
                                        <?php if (! empty($certificate['revoke_reason'])): ?>
                                            <span class="block font-normal text-white/60"><?= esc($certificate['revoke_reason']) ?></span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="flex shrink-0 flex-col gap-3">
                                <?php if (! $revoked): ?>
                                    <a href="<?= esc(locale_url('account/certificates/' . (int) $certificate['id'] . '/download')) ?>" class="btn-brand">
                                        <?= esc(lang('Account.certificates.download')) ?>
                                    </a>
                                <?php endif; ?>
                                <?php // Unprefixed on purpose: this address is
                                      // printed on the document and scanned from
                                      // a QR square by somebody who has never
                                      // been here and has no interest in
                                      // choosing a language first. ?>
                                <a href="<?= esc(rtrim(base_url(), '/') . '/verify/' . rawurlencode((string) $certificate['verify_code'])) ?>"
                                   class="btn-ghost">
                                    <?= esc(lang('Account.certificates.verify')) ?>
                                </a>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <p class="max-w-2xl text-sm text-white/55"><?= esc(lang('Account.certificates.share_note')) ?></p>

        <?php endif; ?>

        <?php // Said on every certificate page, whether or not there is one to
              // show. The school issues its own certificate of completion; the
              // Adobe Certified Professional credential is awarded by Adobe
              // through Certiport, and conflating the two is the sort of
              // half-truth a buyer discovers at the worst moment. ?>
        <p class="max-w-2xl rounded-xl border border-line bg-surface px-4 py-3 text-sm leading-relaxed text-white/65">
            <?= esc(lang('Account.certificates.acp_note')) ?>
            <a href="<?= esc(locale_url('adobe/certification')) ?>" class="font-medium text-brand-red underline decoration-line underline-offset-4">
                <?= esc(lang('Account.certificates.acp_link')) ?>
            </a>
        </p>
    </div>
</div>

<?= $this->endSection() ?>
