<?php helper(['url', 'norlanka']); $locale = current_locale(); ?>
<footer class="<?= ($pageDark ?? false) ? 'on-dark' : '' ?> border-t border-white/10 bg-brand-black">
    <?php // Four content columns now, and the brand block gives up the double
      // width it had. The single row waits for xl rather than lg: a rating
      // badge measures 250px, and four tracks in a 1024 viewport are 206 each,
      // which cut the pills off inside their own column. Two abreast in
      // between, which is roomier than the row it replaces was. ?>
<div class="container-x grid gap-10 py-16 sm:grid-cols-2 xl:grid-cols-4 [&>*]:min-w-0">
        <div>
            <a href="<?= esc(locale_url('')) ?>" class="inline-flex items-center gap-2.5" aria-label="Kukuleganga Giants Forest — home">
                <?php // Larger than the header's mark. The footer is where the
                      // wordmark has room, and at 44px it read as a repeat of the
                      // header rather than a sign-off. ?>
                <?= view('Modules\\Core\\Views\\partials\\logo', ['class' => 'h-16 w-auto sm:h-20']) ?>
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

            <?php // The existing icon row, which already renders only the accounts
                  // that are actually set. ?>
            <div class="mt-6">
                <?= view('Modules\\Core\\Views\\partials\\social_links', ['compact' => false]) ?>
            </div>
        </div>

        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.explore')) ?></h4>
            <?php // The site's real pages, from the same list the header uses. This
                  // column used to carry its own hard-coded copy naming the
                  // manufacturer's pages, so every link 404'd and every label
                  // rendered as the language key it could not resolve. ?>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <?php foreach (site_nav() as $slug => $label): ?>
                    <li><a href="<?= esc(locale_url($slug)) ?>" class="hover:text-white"><?= esc($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.connect')) ?></h4>
            <?php // Contact details, each rendered only when it holds something.
                  // The phone number appeared twice here: a row of its own and
                  // again inside the list added beside it. Social links moved out
                  // to the icon row under the wordmark, where they are icons
                  // rather than words and are not filed under a heading that
                  // says "connect" over an email address. ?>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <?php if ($addr = setting('address', '', 'contact')): ?>
                    <li class="max-w-xs leading-relaxed"><?= esc($addr) ?></li>
                <?php endif; ?>
                <?php foreach (array_unique(array_filter([
                    setting('phone', '', 'contact'),
                    setting('phone_alt', '', 'contact'),
                ])) as $num): ?>
                    <li><a href="tel:<?= esc(preg_replace('/[^0-9+]/', '', $num), 'attr') ?>" class="hover:text-white"><?= esc($num) ?></a></li>
                <?php endforeach; ?>
                <?php // An address has no spaces in it, so the default wrapping
                      // rules cannot break it anywhere. A grid column's automatic
                      // minimum is its content's minimum, so one 264px-wide
                      // unbreakable string widened this column past its share and
                      // pushed the whole footer 18px beyond the viewport at every
                      // width from 768 up. break-words lets it wrap mid-address
                      // when there is no room; min-w-0 lets the column agree to be
                      // narrower than its content once it can. ?>
                <?php if ($mail = setting('email', '', 'contact')): ?>
                    <li><a href="mailto:<?= esc($mail, 'attr') ?>" class="break-words hover:text-white"><?= esc($mail) ?></a></li>
                <?php endif; ?>
            </ul>
        </div>

        <?php // The ratings, where somebody who has read to the bottom of the
              // page is deciding whether to trust it. Same partial as the hero,
              // stacked and a size down to fit a footer column; it renders
              // nothing at all when neither network is configured. ?>
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.reviews.footer_heading')) ?></h4>
            <div class="mt-4">
                <?= view('Modules\\Core\\Views\\partials\\review_badges', ['layout' => 'stack', 'size' => 'sm']) ?>
            </div>
        </div>
    </div>
    <div class="border-t border-white/10">
        <div class="container-x flex flex-col items-center justify-between gap-2 py-6 text-xs text-white/40 sm:flex-row">
            <?php // The brand was hard-coded here, which is why it survived every
                  // settings and translation fix: it was in neither. It follows
                  // site_name now, like the loading screen and the logo. ?>
            <p>&copy; <?= date('Y') ?> <?= esc(setting('site_name', '')) ?>. <?= esc(lang('Site.footer.rights')) ?></p>
            <p><?= esc(lang('Site.footer.built')) ?></p>
        </div>
    </div>
</footer>
