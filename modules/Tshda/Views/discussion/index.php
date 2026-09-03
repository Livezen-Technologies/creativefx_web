<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [['label' => lang('Site.discussion.title')]],
    'eyebrow' => lang('Site.nav.media_centre'),
    'heading' => lang('Site.discussion.title'),
    'intro'   => lang('Site.discussion.intro'),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-3xl">
        <?php if ($topics === []): ?>
            <p class="rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.discussion.none')) ?></p>
        <?php else: ?>
            <ul class="space-y-4" role="list">
                <?php foreach ($topics as $topic): $open = \Modules\Tshda\Models\DiscussionTopicModel::isOpen($topic); ?>
                    <li class="rounded-2xl border border-line bg-surface p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <h2 class="text-lg font-semibold leading-snug">
                                <a href="<?= esc(locale_url('discussion/' . $topic['slug'])) ?>" class="transition hover:text-brand-red"><?= esc(t_field($topic['title'])) ?></a>
                            </h2>
                            <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider <?= $open ? 'bg-brand-red/10 text-brand-red' : 'bg-white/10 text-white/50' ?>">
                                <?= esc($open ? lang('Site.discussion.open') : lang('Site.discussion.closed')) ?>
                            </span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-white/70"><?= esc(mb_substr(strip_tags(t_field($topic['body'])), 0, 240)) ?>…</p>
                        <?php if (! empty($topic['closes_at'])): ?>
                            <p class="mt-3 text-xs text-white/55"><?= esc(lang('Site.discussion.closes_on')) ?> <?= esc(date('j M Y', strtotime((string) $topic['closes_at']))) ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
