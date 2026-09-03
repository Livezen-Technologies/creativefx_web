<?php
helper(['norlanka', 'url']);

/**
 * The welcome page (Clause 3.9 A).
 *
 * Built to the ICTA Guidelines for Developing Sri Lanka Government Websites:
 * the Authority's identity, the trilingual entry choice, and immediate access
 * to the home page. It is deliberately a standalone document rather than the
 * site layout — the header carries a language switcher, and a page whose only
 * job is to ask which language you want should not already have chosen one.
 *
 * @var list<array{code:string,label:string,native:string}> $locales
 */
$siteName = setting('site_name', 'Tea Small Holdings Development Authority');
$parent   = setting('parent_org', '', 'general');

// The Authority's name in each language, so the choice is legible to the
// person making it. A visitor who reads only Tamil cannot pick "Tamil" off a
// list written in English.
$names = [
    'en' => 'Tea Small Holdings Development Authority',
    'si' => 'කුඩා තේ වතු සංවර්ධන අධිකාරිය',
    'ta' => 'தேயிலை சிறு தோட்ட அபிவிருத்தி அதிகார சபை',
];
$enter = ['en' => 'Enter the website', 'si' => 'වෙබ් අඩවියට පිවිසෙන්න', 'ta' => 'இணையதளத்தில் நுழையவும்'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php // The stored preference is applied before paint, exactly as the main
          // layout does it, so a visitor who has chosen dark does not get a
          // white flash on the first page of the site. ?>
    <script>document.documentElement.classList.add('js');try{if(localStorage.getItem('nl_theme')==='dark')document.documentElement.classList.add('dark');}catch(e){}</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($siteName) ?></title>
    <meta name="description" content="<?= esc($metaDescription, 'attr') ?>">
    <meta name="color-scheme" content="light dark">
    <link rel="canonical" href="<?= esc(base_url('/'), 'attr') ?>">
    <?php foreach ($locales as $l): ?>
        <link rel="alternate" hreflang="<?= esc($l['code'], 'attr') ?>" href="<?= esc(base_url($l['code']), 'attr') ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= esc(base_url('/'), 'attr') ?>">
    <?= view('Modules\Core\Views\partials\favicons', [], ['saveData' => false]) ?>
    <?= vite_tags('resources/js/app.js') ?>
    <?php // A visitor who has already chosen sails straight through. Decided in
          // the browser, from the same key the switcher writes — the server
          // cannot see it, and a server-side redirect on a cached page would
          // send everyone to whoever loaded it first. ?>
    <script>
      try {
        var chosen = localStorage.getItem('nl_locale');
        var known = <?= json_encode(array_column($locales, 'code')) ?>;
        if (chosen && known.indexOf(chosen) !== -1 && !location.search.includes('choose')) {
          location.replace('/' + chosen);
        }
      } catch (e) {}
    </script>
</head>
<body class="min-h-screen bg-brand-black font-sans text-white antialiased">
    <main class="relative flex min-h-screen flex-col overflow-hidden">
        <div class="hero-aurora absolute inset-0 -z-10 opacity-60"></div>

        <div class="container-x flex flex-1 flex-col justify-center py-16">
            <div class="mx-auto w-full max-w-3xl text-center">
                <div class="flex justify-center">
                    <?= view('Modules\Core\Views\partials\logo', ['class' => 'h-20 w-auto sm:h-24'], ['saveData' => false]) ?>
                </div>

                <?php if ($parent !== ''): ?>
                    <p class="mt-8 text-xs font-semibold uppercase tracking-[0.3em] text-brand-red"><?= esc($parent) ?></p>
                <?php endif; ?>

                <h1 class="mt-4 space-y-1.5">
                    <?php foreach (['en', 'si', 'ta'] as $code): ?>
                        <span lang="<?= esc($code, 'attr') ?>" class="block text-xl font-bold leading-snug sm:text-2xl"><?= esc($names[$code]) ?></span>
                    <?php endforeach; ?>
                </h1>

                <p class="mt-6 text-sm text-white/60">Sri Lanka · ශ්‍රී ලංකාව · இலங்கை</p>

                <nav aria-label="Choose a language" class="mt-12">
                    <ul class="grid gap-4 sm:grid-cols-3" role="list">
                        <?php foreach ($locales as $l): ?>
                            <li>
                                <a href="/<?= esc($l['code'], 'attr') ?>"
                                   lang="<?= esc($l['code'], 'attr') ?>"
                                   data-locale="<?= esc($l['code'], 'attr') ?>"
                                   class="welcome-choice group flex flex-col items-center gap-1 rounded-2xl border border-line bg-surface px-6 py-7 transition hover:border-brand-red focus-visible:border-brand-red focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red">
                                    <span class="text-xl font-semibold group-hover:text-brand-red"><?= esc($l['native']) ?></span>
                                    <span class="text-xs uppercase tracking-widest text-white/50"><?= esc($enter[$l['code']] ?? $l['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            </div>
        </div>

        <footer class="border-t border-line">
            <div class="container-x flex flex-wrap items-center justify-between gap-4 py-6 text-xs text-white/50">
                <p>&copy; <?= esc(date('Y')) ?> <?= esc($siteName) ?></p>
                <ul class="flex flex-wrap gap-x-5 gap-y-1" role="list">
                    <li><a href="https://www.gov.lk/" target="_blank" rel="noopener noreferrer" class="transition hover:text-brand-red">www.gov.lk</a></li>
                    <li><a href="https://www.locallanguages.lk/" target="_blank" rel="noopener noreferrer" class="transition hover:text-brand-red">locallanguages.lk</a></li>
                </ul>
            </div>
        </footer>
    </main>

    <script>
      // Remember the choice, so the door is only shown once.
      document.querySelectorAll('.welcome-choice').forEach(function (a) {
        a.addEventListener('click', function () {
          try { localStorage.setItem('nl_locale', a.dataset.locale); } catch (e) {}
        });
      });
    </script>
</body>
</html>
