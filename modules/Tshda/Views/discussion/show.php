<?php
helper(['norlanka', 'url', 'form']);
$this->extend('Modules\Core\Views\layouts\main');

$errors    = session()->getFlashdata('errors') ?? [];
$districts = ['Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Matale', 'Nuwara Eliya', 'Galle', 'Matara', 'Hambantota', 'Ratnapura', 'Kegalle', 'Badulla', 'Monaragala', 'Kurunegala', 'Puttalam'];
?>
<?= $this->section('content') ?>

<?= view('Modules\Tshda\Views\partials\page_head', [
    'crumbs'  => [
        ['label' => lang('Site.discussion.title'), 'url' => locale_url('discussion')],
        ['label' => t_field($topic['title'])],
    ],
    'eyebrow' => lang('Site.discussion.title'),
    'heading' => t_field($topic['title']),
], ['saveData' => false]) ?>

<section class="bg-brand-black py-12">
    <div class="container-x max-w-3xl">
        <div class="leading-relaxed text-white/80"><?= rich_text($topic['body']) ?></div>

        <?php if (! empty($topic['closes_at'])): ?>
            <p class="mt-6 text-sm text-white/55"><?= esc(lang('Site.discussion.closes_on')) ?> <?= esc(date('j F Y', strtotime((string) $topic['closes_at']))) ?></p>
        <?php endif; ?>

        <h2 class="mt-12 text-xl font-semibold"><?= esc(lang('Site.discussion.comments')) ?></h2>

        <?php if (session()->getFlashdata('comment_ok')): ?>
            <p role="status" class="mt-4 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc(lang('Site.discussion.ok')) ?></p>
        <?php endif; ?>

        <?php if ($comments === []): ?>
            <p class="mt-4 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.discussion.no_comments')) ?></p>
        <?php else: ?>
            <ul class="mt-5 space-y-4" role="list">
                <?php foreach ($comments as $comment): ?>
                    <li class="rounded-2xl border border-line bg-surface p-5">
                        <p class="leading-relaxed text-white/85"><?= nl2br(esc($comment['body'])) ?></p>
                        <p class="mt-3 text-xs text-white/50">
                            <?= esc($comment['author']) ?><?= ! empty($comment['district']) ? ' · ' . esc($comment['district']) : '' ?>
                            · <time datetime="<?= esc(date('Y-m-d', strtotime((string) $comment['created_at'])), 'attr') ?>"><?= esc(date('j M Y', strtotime((string) $comment['created_at']))) ?></time>
                        </p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <h2 id="add" class="mt-12 scroll-mt-28 text-xl font-semibold"><?= esc(lang('Site.discussion.add')) ?></h2>

        <?php if (! $open): ?>
            <p class="mt-4 rounded-2xl border border-line bg-surface p-6 text-sm"><?= esc(lang('Site.discussion.err_closed')) ?></p>
        <?php else: ?>
            <?php if ($msg = session()->getFlashdata('error')): ?>
                <p role="alert" class="mt-4 rounded-lg border border-brand-red bg-brand-red/10 px-4 py-3 text-sm font-medium"><?= esc($msg) ?></p>
            <?php endif; ?>

            <form method="post" action="<?= esc(locale_url('discussion/' . $topic['slug'])) ?>" class="mt-5 space-y-5">
                <?= csrf_field() ?>
                <div class="hidden" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <label>
                        <span class="field-label"><?= esc(lang('Site.discussion.author')) ?> *</span>
                        <input type="text" name="author" required maxlength="128" class="field" autocomplete="name" value="<?= esc(old('author'), 'attr') ?>">
                        <?php if (isset($errors['author'])): ?><span class="field-error"><?= esc($errors['author']) ?></span><?php endif; ?>
                    </label>
                    <label>
                        <span class="field-label"><?= esc(lang('Site.discussion.email')) ?></span>
                        <input type="email" name="email" maxlength="128" class="field" autocomplete="email" value="<?= esc(old('email'), 'attr') ?>">
                        <?php if (isset($errors['email'])): ?><span class="field-error"><?= esc($errors['email']) ?></span><?php endif; ?>
                    </label>
                    <label>
                        <span class="field-label"><?= esc(lang('Site.discussion.district')) ?></span>
                        <select name="district" class="field">
                            <option value=""></option>
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= esc($district, 'attr') ?>" <?= old('district') === $district ? 'selected' : '' ?>><?= esc($district) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <label class="block">
                    <span class="field-label"><?= esc(lang('Site.discussion.body')) ?> *</span>
                    <textarea name="body" required rows="5" maxlength="2000" class="field"><?= esc(old('body')) ?></textarea>
                    <?php if (isset($errors['body'])): ?><span class="field-error"><?= esc($errors['body']) ?></span><?php endif; ?>
                </label>

                <p class="text-xs leading-relaxed text-white/55"><?= esc(lang('Site.discussion.moderation_note')) ?></p>
                <button type="submit" class="btn-brand"><?= esc(lang('Site.discussion.submit')) ?></button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?= $this->endSection() ?>
