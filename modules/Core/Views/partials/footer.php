<?php helper(['url', 'norlanka']); $locale = current_locale();

// Same registry the header dropdown uses, so the footer service list can never
// drift from the menu. Guarded for the same reason: the footer must render on a
// checkout where the table does not exist yet.
$services = [];
try {
    $services = model('Modules\Services\Models\ServiceModel')->published();
} catch (\Throwable $e) {
    $services = [];
}

$socials = array_filter([
    'Facebook'  => setting('facebook', '', 'social'),
    'Instagram' => setting('instagram', '', 'social'),
    'YouTube'   => setting('youtube', '', 'social'),
    'TikTok'    => setting('tiktok', '', 'social'),
    'LinkedIn'  => setting('linkedin', '', 'social'),
], static fn ($url) => trim((string) $url) !== '');

$email    = (string) setting('email', '', 'contact');
$phone    = (string) setting('phone', '', 'contact');
$address  = (string) setting('address', '', 'contact');
$whatsapp = preg_replace('/[^0-9+]/', '', (string) setting('whatsapp', '', 'contact'));
?>
<footer class="<?= ($pageDark ?? false) ? 'on-dark' : '' ?> border-t border-white/10 bg-brand-black">
    <div class="container-x grid gap-10 py-16 md:grid-cols-2 lg:grid-cols-4">
        <div>
            <a href="<?= esc(locale_url('')) ?>" class="inline-flex items-center gap-2.5" aria-label="CreativeFX — home">
                
                <img src="/media/brand/cfx-wordmark.svg" alt="CreativeFX" width="212" height="24" class="h-6 w-auto">
            </a>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/60">
                <?php
                // The tagline setting is a single plain string, so it cannot carry
                // translations. Show the localized strapline unless an admin has
                // set their own wording.
                $tagline = trim((string) setting('tagline', ''));
                echo esc($tagline === '' ? lang('Site.footer.built') : $tagline);
                ?>
            </p>

            <!-- Newsletter. Posts to the same lead inbox as everything else, so
                 signups are not stranded in a third-party list. -->
            <form method="post" action="<?= esc(locale_url('subscribe')) ?>" class="mt-6 max-w-sm">
                <?= csrf_field() ?>
                <label for="footer-newsletter" class="block text-xs leading-relaxed text-white/50">
                    <?= esc(lang('Site.footer.newsletter.title')) ?>
                </label>
                <?php if ($msg = session('message')): ?>
                    <p class="mt-2 text-xs text-brand-red" role="status"><?= esc($msg) ?></p>
                <?php elseif ($err = session('error')): ?>
                    <p class="mt-2 text-xs text-brand-red" role="alert"><?= esc($err) ?></p>
                <?php endif; ?>
                <div class="mt-3 flex gap-2">
                    <input type="email" id="footer-newsletter" name="email" required
                           placeholder="<?= esc(lang('Site.footer.newsletter.email'), 'attr') ?>"
                           class="min-w-0 flex-1 rounded-lg border border-white/15 bg-white/[0.03] px-3 py-2.5 text-sm text-white placeholder:text-white/30 focus:border-brand-red focus:outline-none">
                    <button type="submit" class="flex-none rounded-lg bg-brand-red px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-brand-ink transition hover:bg-brand-red/85">
                        <?= esc(lang('Site.footer.newsletter.submit')) ?>
                    </button>
                </div>
            </form>
        </div>

        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.explore')) ?></h4>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <li><a href="<?= esc(locale_url('our-story')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.story')) ?></a></li>
                <li><a href="<?= esc(locale_url('services')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.services')) ?></a></li>
                <li><a href="<?= esc(locale_url('portfolio')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.portfolio')) ?></a></li>
                <li><a href="<?= esc(locale_url('contact')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.contact')) ?></a></li>
                <li><a href="<?= esc(locale_url('quote')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.quote')) ?></a></li>
            </ul>
        </div>

        <?php if ($services !== []): ?>
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.services')) ?></h4>
                <ul class="mt-4 space-y-2 text-sm text-white/70">
                    <?php foreach ($services as $service): ?>
                        <li><a href="<?= esc(locale_url('services/' . $service['slug'])) ?>" class="hover:text-white"><?= esc(t_field($service['name'] ?? [])) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.connect')) ?></h4>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <?php if ($phone !== ''): ?>
                    <li><a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $phone), 'attr') ?>" class="hover:text-white"><?= esc($phone) ?></a></li>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <li><a href="mailto:<?= esc($email, 'attr') ?>" class="hover:text-white"><?= esc($email) ?></a></li>
                <?php endif; ?>
                <?php if ($whatsapp !== ''): ?>
                    <li><a href="https://wa.me/<?= esc(ltrim($whatsapp, '+'), 'attr') ?>" class="hover:text-white" target="_blank" rel="noopener">WhatsApp</a></li>
                <?php endif; ?>
                <?php if ($address !== ''): ?>
                    <li class="pt-1 text-white/50"><?= nl2br(esc($address)) ?></li>
                <?php endif; ?>
            </ul>

            <?php if ($socials !== []): ?>
                <h4 class="mt-6 text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.follow')) ?></h4>
                <ul class="mt-3 flex flex-wrap gap-2">
                    <?php foreach ($socials as $name => $url): ?>
                        <li>
                            <a href="<?= esc($url, 'attr') ?>" target="_blank" rel="noopener"
                               class="inline-flex rounded-lg border border-white/15 px-3 py-1.5 text-xs text-white/70 transition hover:border-brand-red hover:text-white">
                                <?= esc($name) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-x flex flex-col items-center justify-between gap-2 py-6 text-xs text-white/40 sm:flex-row">
            <p>&copy; <?= date('Y') ?> CreativeFX. <?= esc(lang('Site.footer.rights')) ?></p>
            <p><?= esc(lang('Site.footer.built')) ?></p>
        </div>
    </div>
</footer>
