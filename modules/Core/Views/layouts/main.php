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

// Clause 3.10: the date of last update is displayed on every page, derived from
// the record the page was rendered from rather than typed. A CMS page brings
// its own updated_at; a controller-driven listing can pass its own; a page with
// nothing behind it shows no date at all, because an automatic date that is not
// the truth is worse than no date.
$lastUpdated = $lastUpdated ?? ($page['updated_at'] ?? null);
?>
<!DOCTYPE html>
<html lang="<?= esc($locale) ?>">
<head>
    <!-- Light ships as the default: a public-information portal is read in
         daylight, on cheap screens, often by people who did not choose to be
         here. Dark is kept as a preference — the ICTA guidelines ask for
         legibility, not for one ground — and the script applies it before any
         paint when the visitor has chosen it, so switching never flashes.
         Both grounds are maintained: :root carries the light palette, .on-dark
         and html.dark the deep-green one, and the logo renders in whichever
         colourway reads against the ground it lands on. -->
    <script>document.documentElement.classList.add('js');try{if(localStorage.getItem('nl_theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}try{if(sessionStorage.getItem('nl_preloaded'))document.documentElement.classList.add('preloaded');}catch(e){}</script>
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
    <meta name="theme-color" content="#0F6B45">
    <meta name="color-scheme" content="light dark">
    <?= view('Modules\Core\Views\partials\favicons', [], ['saveData' => false]) ?>
    <?php // Only present when reCAPTCHA is fully configured, so a site that does
          // not use it never loads Google's script and never gets its cookie. ?>
    <?php if (\Modules\Core\Libraries\Recaptcha::isActive()): ?>
    <meta name="recaptcha-site-key" content="<?= esc(\Modules\Core\Libraries\Recaptcha::siteKey(), 'attr') ?>">
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

    <?= view('Modules\Core\Views\partials\header', ['pageDark' => $pageDark ?? false], ['saveData' => false]) ?>

    <main id="main">
        <?= $this->renderSection('content') ?>
    </main>

    <?php // view() rather than $this->include(): include's second argument is
          // render options, not data, so the variables below were never reaching
          // the partial and the last-updated stamp never rendered. saveData is
          // off so neither partial can leak its locals into the next render on
          // the shared renderer. ?>
    <?= view('Modules\Core\Views\partials\footer', [
        'pageDark'    => $pageDark ?? false,
        'lastUpdated' => $lastUpdated,
    ], ['saveData' => false]) ?>

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
