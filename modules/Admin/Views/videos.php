<?php
helper('norlanka');
$this->extend('Modules\Admin\Views\layout');
$inputCls = 'w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';
?>
<?= $this->section('content') ?>
<form method="post" action="<?= site_url('admin/videos') ?>" class="max-w-3xl space-y-8">
    <?= csrf_field() ?>

    <fieldset class="rounded-xl border border-white/10 p-5">
        <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red">Background film</legend>
        <p class="mb-4 text-xs text-white/40">Leave the source empty to use the animated brand hero. Point it at an S3/CDN MP4 to enable a real film.</p>
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-xs uppercase tracking-widest text-white/50">Source (MP4) path/URL</label>
                <input type="text" name="src_path" value="<?= esc($video['src_path'] ?? '') ?>" class="<?= $inputCls ?>">
            </div>
            <div>
                <label class="mb-1 block text-xs uppercase tracking-widest text-white/50">Poster image</label>
                <input type="text" name="poster_path" value="<?= esc($video['poster_path'] ?? '') ?>" class="<?= $inputCls ?>">
            </div>
        </div>
    </fieldset>

    <fieldset class="rounded-xl border border-white/10 p-5">
        <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red">Audio tracks</legend>
        <?php foreach ($video['tracks'] ?? [] as $t): ?>
            <div class="mb-4 grid gap-3 sm:grid-cols-[3rem,1fr,2fr] sm:items-center">
                <span class="text-xs uppercase text-white/40"><?= esc($t['locale']) ?></span>
                <input type="text" name="tracks[<?= (int) $t['id'] ?>][label]" value="<?= esc($t['label']) ?>" placeholder="Label" class="<?= $inputCls ?>">
                <input type="text" name="tracks[<?= (int) $t['id'] ?>][audio_path]" value="<?= esc($t['audio_path']) ?>" placeholder="Audio path" class="<?= $inputCls ?>">
            </div>
        <?php endforeach; ?>
    </fieldset>

    <fieldset class="rounded-xl border border-white/10 p-5">
        <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red">Subtitles (WebVTT)</legend>
        <?php foreach ($video['subtitles'] ?? [] as $s): ?>
            <div class="mb-4 grid gap-3 sm:grid-cols-[3rem,1fr] sm:items-center">
                <span class="text-xs uppercase text-white/40"><?= esc($s['locale']) ?></span>
                <input type="text" name="subs[<?= (int) $s['id'] ?>][vtt_path]" value="<?= esc($s['vtt_path']) ?>" placeholder="VTT path" class="<?= $inputCls ?>">
            </div>
        <?php endforeach; ?>
    </fieldset>

    <button class="btn-brand">Save</button>
</form>
<?= $this->endSection() ?>
