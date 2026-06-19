<?php helper(['url', 'norlanka']); $locale = current_locale(); ?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? setting('site_name', 'Norlanka')) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?? setting('tagline', '')) ?>">
    <meta name="theme-color" content="#000000">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?= vite_tags('resources/js/app.js') ?>
    <?php if ($ga = setting('ga4_measurement_id', '', 'analytics')): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc($ga) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= esc($ga, 'js') ?>');</script>
    <?php endif; ?>
    <?= $this->renderSection('head') ?>
</head>
<body class="min-h-screen bg-brand-black font-sans text-white antialiased">
    <?= $this->include('Modules\Core\Views\partials\header') ?>

    <main id="main">
        <?= $this->renderSection('content') ?>
    </main>

    <?= $this->include('Modules\Core\Views\partials\footer') ?>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
