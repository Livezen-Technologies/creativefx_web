<?php
helper(['norlanka', 'url']);

/**
 * The sidebar every page of the account area shares.
 *
 * A real `<nav>` with a list inside it, and `aria-current="page"` on the
 * section being read. That attribute is the whole point of this partial: a
 * sighted visitor is told where they are by a filled background, and without
 * `aria-current` somebody using a screen reader hears five identical links and
 * no indication which one they are standing on. The colour is the decoration;
 * the attribute is the information.
 *
 * The joining page for a class marks "courses" as current rather than adding a
 * sixth item. It is reached from that list, it is not a place in its own right,
 * and a navigation that grows an entry per booked class stops being navigation.
 *
 * @var string $current  overview | courses | certificates | invoices | profile
 */
$current = $current ?? '';
$learner = \Modules\Account\Libraries\LearnerAuth::user();

$items = [
    ['key' => 'overview',     'path' => 'account',              'label' => lang('Account.nav.overview')],
    ['key' => 'courses',      'path' => 'account/courses',      'label' => lang('Account.nav.courses')],
    ['key' => 'certificates', 'path' => 'account/certificates', 'label' => lang('Account.nav.certificates')],
    ['key' => 'invoices',     'path' => 'account/invoices',     'label' => lang('Account.nav.invoices')],
    ['key' => 'profile',      'path' => 'account/profile',      'label' => lang('Account.nav.profile')],
];
?>
<nav aria-label="<?= esc(lang('Account.nav.title'), 'attr') ?>" class="lg:sticky lg:top-28">

    <?php if ($learner !== null): ?>
        <div class="rounded-2xl border border-line bg-surface px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-white/50">
                <?= esc(lang('Account.nav.signed_in_as')) ?>
            </p>
            <p class="mt-1 truncate font-semibold"><?= esc($learner['name']) ?></p>
            <p class="truncate text-sm text-white/55"><?= esc($learner['email']) ?></p>
        </div>
    <?php endif; ?>

    <ul class="mt-4 space-y-1">
        <?php foreach ($items as $item): ?>
            <?php $active = $current === $item['key']; ?>
            <li>
                <a href="<?= esc(locale_url($item['path'])) ?>"
                   <?= $active ? 'aria-current="page"' : '' ?>
                   class="flex items-center justify-between rounded-xl border px-4 py-2.5 text-sm font-medium transition
                          <?= $active
                              ? 'border-brand-red bg-brand-red/10 text-white'
                              : 'border-transparent text-white/70 hover:border-line hover:bg-surface hover:text-white' ?>">
                    <span><?= esc($item['label']) ?></span>
                    <?php if ($active): ?>
                        <span aria-hidden="true" class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-red"></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php // Separated from the sections above by a rule, because signing out is
          // not a place to go and putting it in the same list is how somebody
          // leaves the site by mis-clicking "Profile". ?>
    <div class="mt-5 border-t border-line pt-5">
        <a href="<?= esc(locale_url('account/logout')) ?>"
           class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-medium text-white/60 transition hover:text-brand-red">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 6V4.5A1.5 1.5 0 0010.5 3h-5A1.5 1.5 0 004 4.5v11A1.5 1.5 0 005.5 17h5a1.5 1.5 0 001.5-1.5V14M8 10h9m0 0l-2.5-2.5M17 10l-2.5 2.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <?= esc(lang('Account.nav.sign_out')) ?>
        </a>
    </div>
</nav>
