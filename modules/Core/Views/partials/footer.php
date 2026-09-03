<?php helper(['url', 'norlanka']); $locale = current_locale();

// Which columns appear, and the small print. Defaults are what the footer did
// before any of these existed, so an install that has never opened Settings
// looks exactly as it always has.
$showNav     = setting('show_nav', '1', 'footer') !== '0';
$showContact = setting('show_contact', '1', 'footer') !== '0';
$showReviews = setting('show_reviews', '1', 'footer') !== '0';
$privacyUrl  = trim((string) setting('privacy_url', '', 'footer'));
$termsUrl    = trim((string) setting('terms_url', '', 'footer'));

// Columns are counted rather than assumed: with one turned off the remaining
// three should share the row, not leave a gap where the fourth used to be.
$columns = 1 + (int) $showNav + (int) $showContact + (int) $showReviews;
$cols    = ['1' => 'xl:grid-cols-1', '2' => 'xl:grid-cols-2', '3' => 'xl:grid-cols-3', '4' => 'xl:grid-cols-4'][(string) $columns];
?>
<footer class="<?= ($pageDark ?? false) ? 'on-dark' : '' ?> border-t border-white/10 bg-brand-black">
    <?php // Four content columns now, and the brand block gives up the double
      // width it had. The single row waits for xl rather than lg: a rating
      // badge measures 250px, and four tracks in a 1024 viewport are 206 each,
      // which cut the pills off inside their own column. Two abreast in
      // between, which is roomier than the row it replaces was. ?>
<div class="container-x grid gap-10 py-16 sm:grid-cols-2 <?= $cols ?> [&>*]:min-w-0">
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
                <?php // Every account, explicitly: 'only' is passed empty rather
                      // than omitted so this row cannot inherit a shorter list
                      // from an earlier render on the shared renderer. ?>
                <?= view('Modules\\Core\\Views\\partials\\social_links', [
                    'compact' => false,
                    'only'    => [],
                ], ['saveData' => false]) ?>
            </div>
        </div>

        <?php if ($showNav): ?>
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.footer.explore')) ?></h4>
            <?php // The site's real pages, from the same list the header uses. This
                  // column used to carry its own hard-coded copy naming the
                  // manufacturer's pages, so every link 404'd and every label
                  // rendered as the language key it could not resolve. ?>
            <ul class="mt-4 space-y-2 text-sm text-white/70">
                <?php // The footer has its own menu now, so it can be shorter
                      // than the header's — or longer, carrying the pages that
                      // do not earn a place in the bar. Children are listed flat
                      // beneath their parent; a footer column is already a list. ?>
                <?php foreach (site_nav('footer') as $item): ?>
                    <li><a href="<?= esc($item['url'], 'attr') ?>"<?= $item['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '' ?> class="hover:text-white"><?= esc($item['label']) ?></a></li>
                    <?php foreach ($item['children'] as $child): ?>
                        <li class="pl-3"><a href="<?= esc($child['url'], 'attr') ?>"<?= $child['target'] === '_blank' ? ' target="_blank" rel="noopener noreferrer"' : '' ?> class="text-white/55 hover:text-white"><?= esc($child['label']) ?></a></li>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($showContact): ?>
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
        <?php endif; ?>

        <?php if ($showReviews): ?>
        <?php // The ratings, where somebody who has read to the bottom of the
              // page is deciding whether to trust it. Same partial as the hero,
              // stacked and a size down to fit a footer column; it renders
              // nothing at all when neither network is configured. ?>
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc(lang('Site.reviews.footer_heading')) ?></h4>
            <div class="mt-4">
                <?= view('Modules\\Core\\Views\\partials\\review_badges', ['layout' => 'stack', 'size' => 'sm'], ['saveData' => false]) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="border-t border-white/10">
        <div class="container-x flex flex-col items-center justify-between gap-2 py-6 text-xs text-white/40 sm:flex-row">
            <?php // The brand was hard-coded here, which is why it survived every
                  // settings and translation fix: it was in neither. It follows
                  // site_name now, like the loading screen and the logo. ?>
            <?php // A custom copyright line replaces the default wholesale. The
                  // year is still substituted, because a hard-coded one is wrong
                  // every January and nobody notices until somebody else does. ?>
            <?php $copyright = trim((string) setting('copyright', '', 'footer')); ?>
            <p><?= $copyright !== ''
                ? esc(str_replace(['{year}', '{name}'], [date('Y'), (string) setting('site_name', '')], $copyright))
                : '© ' . date('Y') . ' ' . esc(setting('site_name', '')) . '. ' . esc(lang('Site.footer.rights')) ?></p>

            <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-1 sm:justify-end">
                <?php if ($privacyUrl !== ''): ?>
                    <a href="<?= esc(menu_link($privacyUrl), 'attr') ?>" class="hover:text-white/70"><?= esc(lang('Site.footer.privacy')) ?></a>
                <?php endif; ?>
                <?php if ($termsUrl !== ''): ?>
                    <a href="<?= esc(menu_link($termsUrl), 'attr') ?>" class="hover:text-white/70"><?= esc(lang('Site.footer.terms')) ?></a>
                <?php endif; ?>
                <p><?= esc(lang('Site.footer.built')) ?></p>
            </div>
        </div>
    </div>
</footer>
