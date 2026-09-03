<?php helper(['url', 'norlanka']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc(setting('site_name', '')) ?> Admin — Sign in</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= esc(media_src('/favicon-32.png'), 'attr') ?>">
    <?= vite_tags('resources/js/app.js') ?>
</head>
<body class="on-dark flex min-h-screen items-center justify-center bg-brand-black px-4 font-sans text-white antialiased">
<script>if (localStorage.getItem('admin-theme') === 'light') { document.body.classList.remove('on-dark'); }</script>
    <div class="hero-aurora pointer-events-none fixed inset-0 -z-10 opacity-40"></div>

    <div class="w-full max-w-sm rounded-2xl border border-white/10 bg-white/[0.03] p-8 backdrop-blur">
        <div class="mb-7 flex flex-col items-center text-center">
            <?php // The hotel's own mark, from the same partial the site header
                  // uses, rather than the previous brand's wordmark set in text.
                  // This panel is always dark, so .on-dark on the body picks the
                  // white colourway without anything else being said here. ?>
            <a href="<?= esc(site_url('/')) ?>" aria-label="<?= esc(setting('site_name', ''), 'attr') ?> — home">
                <?= view('Modules\\Core\\Views\\partials\\logo', ['class' => 'h-14 w-auto']) ?>
            </a>
            <p class="mt-4 text-xs uppercase tracking-widest text-white/40">Admin sign in</p>
        </div>

        <?php if (session('error')): ?>
            <p class="mb-4 rounded-lg bg-brand-red/15 px-3 py-2 text-sm text-brand-red" role="alert"><?= esc(session('error')) ?></p>
        <?php endif; ?>
        <?php if (session('message')): ?>
            <p class="mb-4 rounded-lg bg-white/10 px-3 py-2 text-sm text-white/80" role="status"><?= esc(session('message')) ?></p>
        <?php endif; ?>

        <form method="post" action="<?= site_url('admin/login') ?>" class="space-y-4">
            <?= csrf_field() ?>
            <?php // The default account's address and password used to be the
                  // field values, with a line underneath naming them as the
                  // credentials. On a site anybody can reach, that is not a
                  // convenience — it is the admin account, published. ?>
            <div>
                <label for="admin-email" class="text-xs uppercase tracking-widest text-white/50">Email</label>
                <input id="admin-email" type="email" name="email" required autocomplete="username"
                       value="<?= esc(old('email'), 'attr') ?>" autofocus
                       class="mt-1 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
            </div>
            <div>
                <label for="admin-password" class="text-xs uppercase tracking-widest text-white/50">Password</label>
                <input id="admin-password" type="password" name="password" required autocomplete="current-password"
                       class="mt-1 w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none">
            </div>
            <button type="submit" class="btn-brand w-full">Sign in</button>
        </form>
    </div>
</body>
</html>
