<?php helper(['url', 'norlanka']); $locale = current_locale(); ?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>" class="dark">
<head>
    <!-- Dark ships as the default (class above, so it holds without JS too);
         the script only strips it when the visitor explicitly chose light. -->
    <script>document.documentElement.classList.add('js');try{if(localStorage.getItem('nl_theme')==='light')document.documentElement.classList.remove('dark');}catch(e){}try{if(sessionStorage.getItem('nl_preloaded'))document.documentElement.classList.add('preloaded');}catch(e){}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? setting('site_name', 'CreativeFX')) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? setting('tagline', '')) ?>">
    <?php // Social sharing (per-page OG image is editable in Admin → Pages).
    $ogTitle = $title ?? setting('site_name', 'CreativeFX');
    $ogDesc  = $metaDescription ?? setting('tagline', '');
    $ogImg   = ! empty($ogImage) ? $ogImage : '/media/brand/cfx-512.png'; ?>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= esc($ogTitle, 'attr') ?>">
    <meta property="og:description" content="<?= esc($ogDesc, 'attr') ?>">
    <meta property="og:image" content="<?= esc(base_url(ltrim($ogImg, '/')), 'attr') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#FFC107">
    <meta name="color-scheme" content="light dark">
    <link rel="icon" type="image/png" sizes="32x32" href="/media/brand/cfx-32.png">
    <link rel="apple-touch-icon" href="/media/brand/cfx-180.png">

    <?php
    // Canonical + hreflang. The same page exists once per locale under a
    // different prefix, so without these the locales compete with each other in
    // search results. Built from the current path with its locale segment
    // swapped, and query strings dropped so ?category=… filters all point at
    // the one canonical listing.
    $segments = explode('/', trim(uri_string(), '/'));
    if (($segments[0] ?? '') === $locale) {
        array_shift($segments);
    }
    $path = implode('/', $segments);
    ?>
    <link rel="canonical" href="<?= esc(rtrim(site_url($locale . ($path === '' ? '' : '/' . $path)), '/'), 'attr') ?>">
    <?php foreach (config('App')->supportedLocales as $alt): ?>
        <link rel="alternate" hreflang="<?= esc($alt, 'attr') ?>" href="<?= esc(rtrim(site_url($alt . ($path === '' ? '' : '/' . $path)), '/'), 'attr') ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= esc(rtrim(site_url(config('App')->defaultLocale . ($path === '' ? '' : '/' . $path)), '/'), 'attr') ?>">

    <?php
    // Organization schema, so search engines have a structured record of who
    // this is. Contact fields are omitted when unset rather than emitted empty.
    $org = array_filter([
        '@context'    => 'https://schema.org',
        '@type'       => 'Organization',
        'name'        => setting('site_name', 'CreativeFX'),
        'url'         => rtrim(base_url(), '/'),
        'logo'        => base_url('media/brand/cfx-512.png'),
        'description' => setting('tagline', ''),
        'email'       => setting('email', '', 'contact'),
        'telephone'   => setting('phone', '', 'contact'),
        'sameAs'      => array_values(array_filter([
            setting('facebook', '', 'social'),
            setting('instagram', '', 'social'),
            setting('youtube', '', 'social'),
            setting('tiktok', '', 'social'),
            setting('linkedin', '', 'social'),
        ], static fn ($v) => trim((string) $v) !== '')),
    ], static fn ($v) => $v !== '' && $v !== [] && $v !== null);
    ?>
    <script type="application/ld+json"><?= json_encode($org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
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
            <img src="/media/brand/cfx-symbol.svg" alt="" width="60" height="60" class="pre-logo">
            <span class="pre-word">Creative<span class="text-brand-red">FX</span></span>
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
