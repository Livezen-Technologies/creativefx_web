<?php
helper(['norlanka', 'url']);

/**
 * The band at the top of every controller-driven page: breadcrumbs, the
 * heading and a one-paragraph introduction.
 *
 * The CMS pages get theirs from a `pagehero` block. These pages are lists and
 * forms rather than prose, and building the same band here means the two kinds
 * of page do not drift apart visually just because of how they are assembled.
 *
 * @var string $eyebrow
 * @var string $heading
 * @var string $intro
 * @var list<array{label:string,url?:string}> $crumbs
 */
$eyebrow = $eyebrow ?? '';
$intro   = $intro ?? '';
$crumbs  = $crumbs ?? [];
?>
<section class="relative overflow-hidden border-b border-line pb-12 pt-32 sm:pt-36">
    <div class="hero-aurora absolute inset-0 -z-10 opacity-40"></div>
    <div class="container-x">
        <?php if ($crumbs !== []): ?>
            <?= view('Modules\Tshda\Views\partials\breadcrumbs', ['crumbs' => $crumbs], ['saveData' => false]) ?>
        <?php endif; ?>

        <?php if ($eyebrow !== ''): ?>
            <p class="mt-6 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc($eyebrow) ?></p>
        <?php endif; ?>

        <h1 class="mt-3 max-w-4xl text-3xl font-bold leading-tight sm:text-5xl"><?= esc($heading) ?></h1>

        <?php if ($intro !== ''): ?>
            <p class="mt-5 max-w-3xl text-lg leading-relaxed text-white/70"><?= esc($intro) ?></p>
        <?php endif; ?>
    </div>
</section>
