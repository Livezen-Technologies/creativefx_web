<?php helper(['url', 'norlanka']); $locale = current_locale(); ?>
<footer class="<?= ($pageDark ?? false) ? 'on-dark' : '' ?> border-t border-white/10 bg-brand-black">
    <div class="container-x grid gap-10 py-16 md:grid-cols-4">
        <div class="md:col-span-2">
            <a href="<?= esc(locale_url('')) ?>" class="inline-flex items-center gap-2.5" aria-label="Magic Corn — home">
                <img src="/media/brand/magiccorn-logo.png" alt="Magic Corn" width="643" height="307" class="h-11 w-auto">
            </a>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/60">
                <?php
                // The tagline setting is a single plain string, so it can't carry
                // translations. Show the localized strapline unless an admin has
                // overridden the setting with their own wording.
                $tagline = (string) setting('tagline', '');
                echo esc($tagline === '' || $tagline === 'Responsible Sourcing · Design · Innovation'
                    ? lang('Site.footer.built')
                    : $tagline);
                ?>
            </p>
        </div>

        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.explore')) ?></h4>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <li><a href="<?= esc(locale_url('about-us')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.about')) ?></a></li>
                <li><a href="<?= esc(locale_url('our-business')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.business')) ?></a></li>
                <li><a href="<?= esc(locale_url('products')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.products')) ?></a></li>
                <li><a href="<?= esc(locale_url('our-locations')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.locations')) ?></a></li>
                <li><a href="<?= esc(locale_url('contact')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.contact')) ?></a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.connect')) ?></h4>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <li><a href="mailto:<?= esc(setting('email', '', 'contact')) ?>" class="hover:text-white"><?= esc(setting('email', 'shankerv@viswakula.com', 'contact')) ?></a></li>
                <?php if ($fb = setting('facebook', '', 'social')): ?>
                    <li><a href="<?= esc($fb) ?>" class="hover:text-white" target="_blank" rel="noopener">Facebook</a></li>
                <?php endif; ?>
                <?php if ($ig = setting('instagram', '', 'social')): ?>
                    <li><a href="<?= esc($ig) ?>" class="hover:text-white" target="_blank" rel="noopener">Instagram</a></li>
                <?php endif; ?>
                <?php if ($tel = setting('phone', '', 'contact')): ?>
                    <li><a href="tel:<?= esc(preg_replace('/[^+0-9]/', '', $tel), 'attr') ?>" class="hover:text-white"><?= esc($tel) ?></a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-x flex flex-col items-center justify-between gap-2 py-6 text-xs text-white/40 sm:flex-row">
            <p>&copy; <?= date('Y') ?> Magic Corn. <?= esc(lang('Site.footer.rights')) ?></p>
            <p><?= esc(lang('Site.footer.built')) ?></p>
        </div>
    </div>
</footer>
