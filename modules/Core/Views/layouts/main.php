<?php
helper(['url', 'norlanka']);
$locale = current_locale();

// Every view that extends this layout has to be able to render without knowing
// the full SEO vocabulary. PageSeo supplies all of these for CMS pages; the 404
// page, and anything else rendered outside that path, supplies none — and an
// undefined variable in a view is an exception, not a blank. Defaulting here
// rather than at each use keeps the head readable and cannot be forgotten by
// the next view that extends this file.
$title           = $title           ?? null;
$metaDescription = $metaDescription ?? '';
$metaKeywords    = $metaKeywords    ?? '';
$canonical       = $canonical       ?? current_url();
$ogTitle         = $ogTitle         ?? null;
$ogDescription   = $ogDescription   ?? null;
$ogImage         = $ogImage         ?? null;
$noIndex         = $noIndex         ?? false;
?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>" class="dark">
<head>
    <!-- Dark ships as the default. The class is on the element itself so it
         holds before any script runs and the first paint is never a light flash;
         the script only strips it when the visitor has explicitly chosen light.
         Both grounds are maintained: :root carries the cream palette, .on-dark
         and html.dark the forest one, and the logo renders in whichever
         colourway reads against the ground it lands on. -->
    <script>document.documentElement.classList.add('js');try{if(localStorage.getItem('nl_theme')==='light')document.documentElement.classList.remove('dark');}catch(e){}try{if(sessionStorage.getItem('nl_preloaded'))document.documentElement.classList.add('preloaded');}catch(e){}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?: setting('site_name', '')) ?></title>
    <meta name="description" content="<?= esc($metaDescription ?: setting('tagline', '')) ?>">
    <?php // Per-page SEO, all editable in Admin → Pages. Each field is emitted
          // only when it holds something: an empty keywords tag or a canonical
          // pointing nowhere is worse than the absence of either.
          //
          // The Open Graph title and description fall back to the page's own
          // through PageSeo, so a page that has not been given separate share
          // copy still shares correctly.
    $ogTitle = $ogTitle ?: ($title ?: setting('site_name', ''));
    $ogDesc  = $ogDescription ?: ($metaDescription ?: setting('tagline', ''));
    $ogImg   = ! empty($ogImage) ? $ogImage
        : ((string) setting('og_default', '', 'brand') ?: '/media/giantforests/Welcome-to-Giants-Forest-1-1.jpg'); ?>
    <?php // One switch turns the whole site away from search engines, for while
          // it is being prepared. It is deliberately a site-wide setting rather
          // than a per-page one, because the mistake it prevents — launching
          // with the staging noindex still on — is site-wide too. ?>
    <?php if (setting('robots', '1', 'seo') === '0'): ?>
    <meta name="robots" content="noindex, nofollow">
    <?php endif; ?>
    <?php $keywords = $metaKeywords ?: (string) setting('meta_keywords', '', 'seo'); ?>
    <?php if ($keywords !== ''): ?>
    <meta name="keywords" content="<?= esc($keywords, 'attr') ?>">
    <?php endif; ?>
    <?php if (! empty($canonical)): ?>
    <link rel="canonical" href="<?= esc($canonical, 'attr') ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= esc(setting('site_name', ''), 'attr') ?>">
    <?php if (! empty($canonical)): ?>
    <meta property="og:url" content="<?= esc($canonical, 'attr') ?>">
    <?php endif; ?>
    <meta property="og:title" content="<?= esc($ogTitle, 'attr') ?>">
    <meta property="og:description" content="<?= esc($ogDesc, 'attr') ?>">
    <meta property="og:image" content="<?= esc(base_url(ltrim($ogImg, '/')), 'attr') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <?php // A page that exists only because a URL did not should not be offered
          // to a crawler; without this the 404 competes in search with the pages
          // it is apologising for. ?>
    <?php if (! empty($noIndex)): ?>
    <meta name="robots" content="noindex, follow">
    <?php endif; ?>
    <meta name="theme-color" content="#346142">
    <meta name="color-scheme" content="light dark">
    <?php // Square icons built from the mark's own G (scripts/make-favicon.py).
          // These used to point straight at the 300x200 lockup, which a browser
          // squeezed into 16px and rendered as a smear — three lines of brush
          // lettering in the space of a word. ?>
    <?php // An uploaded favicon replaces the whole generated set rather than one
          // size of it: mixing a custom 32px with a generated 192px would show
          // two different marks depending on where the browser looked. ?>
    <?php if ($favicon = (string) setting('favicon', '', 'brand')): ?>
    <link rel="icon" href="<?= esc(media_src($favicon), 'attr') ?>">
    <link rel="apple-touch-icon" href="<?= esc(media_src($favicon), 'attr') ?>">
    <?php else: ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= esc(media_src('/favicon-32.png'), 'attr') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= esc(media_src('/favicon-48.png'), 'attr') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= esc(media_src('/favicon-192.png'), 'attr') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= esc(media_src('/apple-touch-icon.png'), 'attr') ?>">
    <?php endif; ?>
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
            <?= view('Modules\\Core\\Views\\partials\\logo', ['class' => 'pre-logo']) ?>
            <span class="pre-word"><?= esc(setting('site_name', '')) ?></span>
            <span class="pre-bar"><span class="pre-bar-fill"></span></span>
        </div>
    </div>

    <?= $this->include('Modules\Core\Views\partials\header', ['pageDark' => $pageDark ?? false]) ?>

    <main id="main">
        <?= $this->renderSection('content') ?>
    </main>

    <?= $this->include('Modules\Core\Views\partials\footer', ['pageDark' => $pageDark ?? false]) ?>

    <?php // Both site-wide. A chat button that exists only where somebody
          // remembered to include it is one a visitor cannot rely on, and the
          // booking dialog is now opened from calls to action on several pages,
          // not just the home hero.
          //
          // After the footer on purpose: a fixed overlay inside a transformed
          // ancestor is positioned against that ancestor rather than the
          // viewport, and the home page's panel column is transformed. Out here
          // it cannot be trapped in a panel. ?>
    <?= $this->include('Modules\Core\Views\partials\booking_modal') ?>
    <?= $this->include('Modules\Core\Views\partials\film_modal') ?>
    <?= $this->include('Modules\Core\Views\partials\whatsapp_widget') ?>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
