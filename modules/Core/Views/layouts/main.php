<?php helper(['url', 'norlanka']); $locale = current_locale(); ?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>" class="dark">
<head>
    <!-- Dark ships as the default (class above, so it holds without JS too);
         the script only strips it when the visitor explicitly chose light. -->
    <script>document.documentElement.classList.add('js');try{if(localStorage.getItem('nl_theme')==='light')document.documentElement.classList.remove('dark');}catch(e){}try{if(sessionStorage.getItem('nl_preloaded'))document.documentElement.classList.add('preloaded');}catch(e){}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? setting('site_name', 'Magic Corn')) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? setting('tagline', '')) ?>">
    <?php // Social sharing (per-page OG image is editable in Admin → Pages).
    $ogTitle = $title ?? setting('site_name', 'Magic Corn');
    $ogDesc  = $metaDescription ?? setting('tagline', '');
    $ogImg   = ! empty($ogImage) ? $ogImage : '/media/giantforests/Welcome-to-Giants-Forest-1-1.jpg'; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= esc($ogTitle, 'attr') ?>">
    <meta property="og:description" content="<?= esc($ogDesc, 'attr') ?>">
    <meta property="og:image" content="<?= esc(base_url(ltrim($ogImg, '/')), 'attr') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#CF2030">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= esc(media_src('/media/giantforests/Kukuleganga-Giants-Forest-Logo-1.png'), 'attr') ?>">
    <link rel="apple-touch-icon" href="<?= esc(media_src('/media/giantforests/Kukuleganga-Giants-Forest-Logo-1.png'), 'attr') ?>">
    <?= vite_tags('resources/js/app.js') ?>
    <?php if ($ga = setting('ga4_measurement_id', '', 'analytics')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($ga) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= esc($ga, 'js') ?>');</script>
    <?php endif; ?>
    <?= $this->renderSection('head') ?>
</head>
<body class="min-h-screen bg-brand-black font-sans text-white antialiased">
    <!-- Brand loading screen (shown once per session; dismissed by preloader.js). -->
    <div id="preloader" aria-hidden="true">
        <div class="pre-inner">
            <img src="/media/giantforests/Kukuleganga-Giants-Forest-Logo-white.png" alt="" width="60" height="60" class="pre-logo">
            <span class="pre-word">Magic Corn</span>
            <span class="pre-bar"><span class="pre-bar-fill"></span></span>
        </div>
    </div>

    <?= $this->include('Modules\Core\Views\partials\header', ['pageDark' => $pageDark ?? false]) ?>

    <main id="main">
        <?= $this->renderSection('content') ?>
    </main>

    <?= $this->include('Modules\Core\Views\partials\footer', ['pageDark' => $pageDark ?? false]) ?>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
