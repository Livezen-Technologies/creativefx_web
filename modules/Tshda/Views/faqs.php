<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.faqs.title')]],
    'eyebrow' => lang('Site.nav.faqs'),
    'heading' => lang('Site.faqs.title'),
    'intro'   => lang('Site.faqs.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-4xl">
        <form method="get" class="flex flex-wrap items-end gap-3" role="search">
            <label class="min-w-[16rem] flex-1">
                <span class="field-label"><?= esc(lang('Site.faqs.search')) ?></span>
                <input type="search" name="q" value="<?= esc($query, 'attr') ?>" class="field">
            </label>
            <button type="submit" class="btn-brand"><?= esc(lang('Site.search.button')) ?></button>
        </form>

        <?php if ($groups === []): ?>
            <p class="mt-10 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.faqs.none')) ?></p>
        <?php else: ?>
            <?php // A category jump list, because the answer somebody wants is
                  // rarely the first one and a page of thirty questions is a
                  // page you scroll past. ?>
            <nav aria-label="<?= esc(lang('Site.faqs.title'), 'attr') ?>" class="mt-8 flex flex-wrap gap-2">
                <?php foreach (array_keys($groups) as $category): ?>
                    <a href="#faq-cat-<?= esc($category, 'attr') ?>"
                       class="rounded-full border border-line bg-surface px-4 py-1.5 text-sm font-medium transition hover:border-brand-red">
                        <?= esc(lang('Site.faqs.cat_' . $category)) ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php foreach ($groups as $category => $rows): ?>
                <section id="faq-cat-<?= esc($category, 'attr') ?>" class="mt-12 scroll-mt-28">
                    <h2 class="text-xl font-semibold"><?= esc(lang('Site.faqs.cat_' . $category)) ?></h2>
                    <div class="mt-4 divide-y divide-line rounded-2xl border border-line">
                        <?php foreach ($rows as $row): ?>
                            <?php // <details> rather than a scripted accordion: it
                                  // opens without JavaScript, it is announced
                                  // correctly by screen readers, and the browser's
                                  // own find-in-page can open it. ?>
                            <details id="faq-<?= (int) $row['id'] ?>" class="group scroll-mt-28">
                                <summary class="flex cursor-pointer list-none items-start gap-3 p-5 text-sm font-semibold transition hover:text-brand-red">
                                    <svg aria-hidden="true" class="mt-0.5 h-4 w-4 shrink-0 text-brand-red transition-transform group-open:rotate-90" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 4 4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    <span><?= esc(t_field($row['question'])) ?></span>
                                </summary>
                                <div class="px-5 pb-5 pl-12 text-sm leading-relaxed text-white/75"><?= rich_text($row['answer']) ?></div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="mt-14 rounded-2xl border border-line bg-surface p-6">
            <h2 class="text-lg font-semibold"><?= esc(lang('Site.nav.feedback')) ?></h2>
            <p class="mt-2 text-sm leading-relaxed text-white/70"><?= esc(lang('Site.feedback.intro')) ?></p>
            <a href="<?= esc(locale_url('feedback')) ?>" class="btn-brand mt-5 inline-flex"><?= esc(lang('Site.feedback.submit')) ?></a>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
