<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * The narrow centred shell the four account forms share.
 *
 * It extends the site layout rather than replacing it, so the header, the
 * footer, the theme tokens, the language switcher and the basket badge are all
 * still there. That is deliberate and it is the opposite of what most sites do
 * with a sign-in page: somebody who arrives here half way through buying a
 * course must be able to get back to the course, and a stripped page with no
 * way out is how a booking is abandoned at the last step.
 *
 * A child form fills two sections:
 *
 *   `form`  — the contents of the card. Required.
 *   `below` — anything under it: the link to the other form, a note. Optional,
 *             and renders as nothing when a child does not define it.
 *
 * One column, about twenty-six rems wide, because a single-column form is the
 * only shape that survives a phone, a screen reader and a password manager at
 * the same time.
 *
 * @var string $heading
 * @var string $intro
 * @var string $eyebrow
 */
$heading = $heading ?? '';
$intro   = $intro   ?? '';
$eyebrow = $eyebrow ?? '';

// `notice` and `error` are the platform's two flash keys — the cart, the
// currency switch and the account area all set the same pair. A third name
// here would mean a confirmation set by this controller and read by nobody.
$error  = session()->getFlashdata('error');
$notice = session()->getFlashdata('notice');
?>

<?= $this->section('content') ?>

<section class="relative overflow-hidden pb-20 pt-32 sm:pt-36">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-40"></div>

    <div class="container-x">
        <div class="mx-auto w-full max-w-[26rem]">

            <?php if ($eyebrow !== ''): ?>
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc($eyebrow) ?></p>
            <?php endif; ?>

            <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-4xl"><?= esc($heading) ?></h1>

            <?php if ($intro !== ''): ?>
                <p class="mt-4 leading-relaxed text-white/70"><?= esc($intro) ?></p>
            <?php endif; ?>

            <?php // Announced the moment the page loads, because both of these
                  // are the answer to something the visitor has just done and
                  // neither has any other way of reaching somebody who cannot
                  // see the page. role="alert" is assertive and role="status"
                  // polite, which is the right way round: a refusal interrupts,
                  // a confirmation waits its turn. ?>
            <?php if ($error): ?>
                <p role="alert" class="mt-7 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
                    <?= esc($error) ?>
                </p>
            <?php endif; ?>

            <?php if ($notice): ?>
                <p role="status" class="mt-7 rounded-xl border border-gold bg-gold/10 px-4 py-3 text-sm font-medium">
                    <?= esc($notice) ?>
                </p>
            <?php endif; ?>

            <div class="mt-8 rounded-3xl border border-line bg-surface p-6 sm:p-8">
                <?= $this->renderSection('form') ?>
            </div>

            <?= $this->renderSection('below') ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
