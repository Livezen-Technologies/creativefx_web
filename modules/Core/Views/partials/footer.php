<?php helper(['url', 'norlanka']); $locale = current_locale(); ?>
<footer class="on-dark border-t border-white/10 bg-brand-black">
    <div class="container-x grid gap-10 py-16 md:grid-cols-4">
        <div class="md:col-span-2">
            <span class="text-2xl font-bold tracking-widest">NOR<span class="text-brand-red">LANKA</span></span>
            <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/60">
                <?= esc(setting('tagline', 'Responsible Sourcing · Design · Innovation')) ?>
            </p>
        </div>

        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.explore')) ?></h4>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <li><a href="<?= esc(locale_url('our-story')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.story')) ?></a></li>
                <li><a href="<?= esc(locale_url('our-expertise')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.expertise')) ?></a></li>
                <li><a href="<?= esc(locale_url('showroom')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.showroom')) ?></a></li>
                <li><a href="<?= esc(locale_url('careers')) ?>" class="hover:text-white"><?= esc(lang('Site.nav.careers')) ?></a></li>
            </ul>
        </div>

        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.connect')) ?></h4>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <li><a href="mailto:<?= esc(setting('email', '', 'contact')) ?>" class="hover:text-white"><?= esc(setting('email', 'hello@norlanka.com', 'contact')) ?></a></li>
                <li><a href="<?= esc(setting('linkedin', '#', 'social')) ?>" class="hover:text-white" target="_blank" rel="noopener">LinkedIn</a></li>
                <li><a href="<?= esc(setting('instagram', '#', 'social')) ?>" class="hover:text-white" target="_blank" rel="noopener">Instagram</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-x flex flex-col items-center justify-between gap-2 py-6 text-xs text-white/40 sm:flex-row">
            <p>&copy; <?= date('Y') ?> Norlanka. <?= esc(lang('Site.footer.rights')) ?></p>
            <p><?= esc(lang('Site.footer.built')) ?></p>
        </div>
    </div>
</footer>
