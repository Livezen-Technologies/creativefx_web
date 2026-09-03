<?php
helper(['norlanka', 'url', 'form']);

/**
 * Subscribe to alerts (Clause 3.13).
 *
 * Posts to its own endpoint and comes back to the page it was on, so the same
 * block can sit on the announcements listing, the home page and the footer
 * without any of them needing a route of their own.
 *
 * @var list<string> $topics
 */
$topics = \Modules\Tshda\Models\SubscriberModel::TOPICS;
?>
<div class="rounded-2xl border border-line bg-surface p-6 sm:p-8">
    <h2 class="text-xl font-semibold"><?= esc(lang('Site.alerts.title')) ?></h2>
    <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(lang('Site.alerts.intro')) ?></p>

    <?php if (session()->getFlashdata('subscribe_ok')): ?>
        <p role="status" class="mt-4 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm font-medium">
            <?= esc(session()->getFlashdata('subscribe_ok')) ?>
        </p>
    <?php endif; ?>
    <?php if (session()->getFlashdata('subscribe_error')): ?>
        <p role="alert" class="mt-4 rounded-lg border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
            <?= esc(session()->getFlashdata('subscribe_error')) ?>
        </p>
    <?php endif; ?>

    <form method="post" action="<?= esc(locale_url('alerts')) ?>" class="mt-5 space-y-4">
        <?= csrf_field() ?>
        <?php // Bots fill every field. A person never sees this one. ?>
        <div class="hidden" aria-hidden="true">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <label>
                <span class="field-label"><?= esc(lang('Site.alerts.name')) ?></span>
                <input type="text" name="name" class="field" autocomplete="name">
            </label>
            <label>
                <span class="field-label"><?= esc(lang('Site.alerts.email')) ?> *</span>
                <input type="email" name="email" required class="field" autocomplete="email">
            </label>
        </div>

        <fieldset>
            <legend class="field-label"><?= esc(lang('Site.alerts.topics')) ?></legend>
            <div class="flex flex-wrap gap-x-5 gap-y-2">
                <?php foreach ($topics as $topic): ?>
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="topics[]" value="<?= esc($topic, 'attr') ?>"
                               class="h-4 w-4 rounded border-line text-brand-red focus-visible:ring-2 focus-visible:ring-brand-red">
                        <span><?= esc(lang('Site.alerts.topic_' . $topic)) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <button type="submit" class="btn-brand"><?= esc(lang('Site.alerts.subscribe')) ?></button>
        <p class="text-xs leading-relaxed text-white/50"><?= esc(lang('Site.alerts.optin_note')) ?></p>
    </form>
</div>
