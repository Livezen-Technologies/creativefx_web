<?php
helper(['norlanka', 'url']);

/**
 * The band at the top of every controller-driven page: breadcrumbs, the
 * heading, an introduction and, optionally, a right-hand slot.
 *
 * CMS pages get theirs from a `pagehero` block. These pages are catalogues,
 * schedules and forms rather than prose, and building the same band here means
 * the two kinds of page do not drift apart visually just because of how they
 * happen to be assembled.
 *
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro
 * @var string $aside    optional pre-escaped HTML, right-aligned on wide screens
 * @var list<array{label:string,url?:string}> $crumbs
 */
$eyebrow = $eyebrow ?? '';
$intro   = $intro ?? '';
$aside   = $aside ?? '';
$crumbs  = $crumbs ?? [];
?>
<section class="relative overflow-hidden border-b border-line pb-12 pt-32 sm:pt-36">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-40"></div>
    <div class="container-x">
        <?php if ($crumbs !== []): ?>
            <?= view('Modules\Site\Views\partials\breadcrumbs', ['crumbs' => $crumbs], ['saveData' => false]) ?>
        <?php endif; ?>

        <div class="mt-6 flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0 max-w-3xl">
                <?php if ($eyebrow !== ''): ?>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc($eyebrow) ?></p>
                <?php endif; ?>

                <h1 class="mt-3 text-3xl font-bold leading-tight sm:text-5xl"><?= esc($heading) ?></h1>

                <?php if ($intro !== ''): ?>
                    <p class="mt-5 text-lg leading-relaxed text-white/70"><?= esc($intro) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($aside !== ''): ?>
                <div class="shrink-0"><?= $aside ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>
