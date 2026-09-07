<?= $this->extend('Modules\Core\Views\layouts\main') ?>

<?php
/**
 * One free resource, and the form that hands it over.
 *
 * The page has to be worth reading whether or not anybody ever presses the
 * button. That is not generosity: the body copy is what ranks, and a page whose
 * only content is "enter your email to receive the PDF" ranks for nothing, gets
 * linked to by nobody, and collects addresses from a trickle of visitors it did
 * not earn. So the substance of the thing — what is on it, a real sample from
 * it, how to use it — is on the page, in full, above the form.
 *
 * Three states, and the panel is the only part that changes between them:
 *
 *   **Ungated, with a file.** One button. Still a POST, so the count is not
 *   inflated by every crawler and prefetching browser that opens the page.
 *   **Gated, with a file.** Address, an unticked consent box, and the file
 *   arrives when the button is pressed.
 *   **No file yet.** The same short form, and the page says plainly that the
 *   guide is still being written rather than offering a download that 404s.
 *
 * The consent box is never pre-ticked and the download never depends on it. A
 * pre-ticked box is not consent under either the GDPR or the Sri Lanka PDPA,
 * and making the file conditional on it would make the consent worthless as
 * evidence even where it was given.
 *
 * @var array  $resource
 * @var bool   $hasFile       a readable file exists on disk
 * @var bool   $asksForEmail  gated, or nothing to hand over yet
 * @var string $utmQuery      the campaign parameters, encoded, or ''
 * @var array  $errors
 * @var bool   $queued        set only by a successful submit with no file
 * @var list<array> $crumbs
 */
helper(['norlanka', 'catalog', 'commerce', 'url']);

/** A field's error message, or an empty string. */
$errorFor = static fn (string $field): string => (string) ($errors[$field] ?? '');

$problem = session()->getFlashdata('resource_error');
$action  = locale_url('resources/' . $resource['slug']) . $utmQuery;

// Accurate in each state rather than one label for all three. "Download"
// promises a file, which is a promise this page can only keep when there is
// one; "Send it to me" is what actually happens when there is not.
$submitLabel = $hasFile ? lang('Catalog.resources.download') : lang('Catalog.resources.get');
?>

<?= $this->section('head') ?>
<?= $schema ?? '' ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?= view('Modules\Site\Views\partials\page_head', [
    'eyebrow' => lang('Catalog.resources.title'),
    'heading' => t_field($resource['title']),
    'intro'   => t_field($resource['summary'] ?? ''),
    'crumbs'  => $crumbs,
], ['saveData' => false]) ?>

<div class="container-x grid gap-12 py-12 lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-14">

    <div class="min-w-0 space-y-10">
        <?php if (! empty($resource['hero_image'])): ?>
            <?php // Decorative: everything it shows is said in the copy below
                  // it, and a cover image described twice is a screen reader
                  // reading the page twice. ?>
            <img src="<?= esc(media_src($resource['hero_image']), 'attr') ?>" alt=""
                 class="w-full rounded-2xl border border-line object-cover"
                 loading="lazy" width="1280" height="720">
        <?php endif; ?>

        <?php if ($body = rich_text($resource['body'] ?? '')): ?>
            <div class="prose-site"><?= $body ?></div>
        <?php endif; ?>

        <?php // The route onward, for the nine readers in ten who take the
              // sheet and do not book anything today. It is a link rather than
              // a pitch, because this page's job was done at the download. ?>
        <section class="rounded-3xl border border-line bg-surface p-7 sm:p-9">
            <h2 class="text-xl font-bold sm:text-2xl"><?= esc(lang('Catalog.courses.title')) ?></h2>
            <p class="mt-3 max-w-2xl text-white/70"><?= esc(lang('Catalog.courses.meta')) ?></p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="<?= esc(locale_url('courses')) ?>" class="btn-brand"><?= esc(lang('Catalog.courses.all')) ?></a>
                <a href="<?= esc(locale_url('resources')) ?>" class="btn-ghost"><?= esc(lang('Catalog.resources.title')) ?></a>
            </div>
        </section>
    </div>

    <?php // Sticky from lg up; at the top of the column on a phone, where a
          // floating box would cover the copy it is meant to be selling. ?>
    <aside class="lg:sticky lg:top-28 lg:self-start" aria-labelledby="get-it">
        <div class="rounded-2xl border border-line bg-surface p-6">
            <h2 id="get-it" class="text-lg font-semibold"><?= esc(t_field($resource['title'])) ?></h2>

            <?php if ($queued): ?>
                <?php // Reached only by a successful submit, and only for a
                      // resource with no file. It thanks them for the thing
                      // that actually happened — an address recorded — and
                      // repeats what they are waiting for. Nothing here says
                      // "check your inbox": there is nothing in it yet. ?>
                <p role="status" class="mt-4 rounded-xl border border-gold/40 px-4 py-3 text-sm font-medium">
                    <?= esc(lang('Commerce.corporate.thanks.heading')) ?>
                </p>
                <p class="mt-4 text-sm leading-relaxed text-white/70"><?= esc(lang('Catalog.resources.pending')) ?></p>
                <a href="<?= esc(locale_url('resources')) ?>" class="btn-ghost mt-5 w-full">
                    <?= esc(lang('Catalog.resources.title')) ?>
                </a>
            <?php else: ?>
                <?php if (! $hasFile): ?>
                    <p class="mt-4 text-sm leading-relaxed text-white/70"><?= esc(lang('Catalog.resources.pending')) ?></p>
                <?php endif; ?>

                <?php if ($problem): ?>
                    <p role="alert" class="mt-4 rounded-xl border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium">
                        <?= esc($problem) ?>
                    </p>
                <?php endif; ?>

                <?php // The action carries the campaign parameters, because
                      // LeadModel::utm() reads them from the query string of
                      // the request that creates the lead — and that request
                      // is this POST. Posting to a bare address attributes
                      // every download to nothing. ?>
                <form method="post" action="<?= esc($action) ?>" class="mt-5 space-y-4">
                    <?= csrf_field() ?>

                    <?php // A field no human ever sees and nearly every script
                          // fills. Hidden from assistive technology as well as
                          // from the eye, and out of the tab order, so a screen
                          // reader user is never asked to fill it in. ?>
                    <div class="hidden" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
                    </div>

                    <?php if ($asksForEmail): ?>
                        <label class="block">
                            <span class="field-label"><?= esc(lang('Catalog.resources.email')) ?></span>
                            <input id="email" name="email" type="email" required maxlength="191" autocomplete="email"
                                   class="field" value="<?= esc(old('email', '', false), 'attr') ?>"
                                   <?= $errorFor('email') ? 'aria-invalid="true" aria-describedby="err-email"' : '' ?>>
                            <?php if ($e = $errorFor('email')): ?>
                                <span id="err-email" class="field-error"><?= esc($e) ?></span>
                            <?php endif; ?>
                        </label>

                        <?php // Unticked in the markup and never assumed, and
                              // the button works either way: asking for a cheat
                              // sheet is not agreeing to a mailing list. ?>
                        <label class="flex items-start gap-3 text-sm text-white/70">
                            <input type="checkbox" id="marketing_opt_in" name="marketing_opt_in" value="1"
                                   class="mt-0.5 h-4 w-4 shrink-0 accent-brand-red"
                                   <?= old('marketing_opt_in', '', false) ? 'checked' : '' ?>>
                            <span><?= esc(lang('Catalog.resources.consent')) ?></span>
                        </label>
                    <?php endif; ?>

                    <button type="submit" class="btn-brand w-full"><?= esc($submitLabel) ?></button>

                    <?php if ($asksForEmail): ?>
                        <p class="text-xs leading-relaxed text-white/50"><?= esc(lang('Commerce.corporate.form.privacy_note')) ?></p>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?= $this->endSection() ?>
