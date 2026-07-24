<?php
helper('norlanka');
$this->extend('Modules\Admin\Views\layout');
$inputCls = 'w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';

/**
 * A labelled path input + drag-and-drop uploader bound to it. Files land in
 * the media library (public/media/{folder}) and the returned URL fills the
 * input — which stays hand-editable for CDN/S3 paths.
 */
$fileField = static function (string $label, string $name, string $value, string $accept, string $folder, string $hint = '') use ($inputCls): void {
    $fid = 'f_' . preg_replace('/[^a-z0-9_]/i', '_', $name); ?>
    <div>
        <label class="mb-1 block text-xs uppercase tracking-widest text-white/50"><?= esc($label) ?></label>
        <input type="text" id="<?= esc($fid, 'attr') ?>" name="<?= esc($name, 'attr') ?>" value="<?= esc($value) ?>" placeholder="/media/…" class="<?= $inputCls ?> mb-2">
        <label data-dropzone="<?= esc($fid, 'attr') ?>" data-folder="<?= esc($folder, 'attr') ?>" class="flex items-center gap-3 px-4 py-3">
            <input type="file" accept="<?= esc($accept, 'attr') ?>" class="hidden">
            <svg class="h-4 w-4 flex-none text-white/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"/></svg>
            <span class="text-xs text-white/55">Drop a file here or <span class="font-semibold text-brand-red">browse</span><?= $hint !== '' ? ' — ' . esc($hint) : '' ?></span>
            <span class="dz-status ml-auto text-xs text-white/40"></span>
        </label>
        <div class="dz-preview mt-2"></div>
    </div>
<?php };
?>
<?= $this->section('content') ?>
<form method="post" action="<?= site_url('admin/videos') ?>" class="max-w-3xl space-y-8">
    <?= csrf_field() ?>

    <fieldset class="rounded-xl border border-white/10 p-5">
        <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red">Background film</legend>
        <p class="mb-4 text-xs text-white/40">Leave the source empty to use the animated brand hero. Upload an MP4 (or point at an S3/CDN URL) to enable a real film.</p>
        <div class="space-y-5">
            <?php $fileField('Source (MP4)', 'src_path', $video['src_path'] ?? '', 'video/mp4,video/webm', 'video', 'MP4/WebM'); ?>
            <?php $fileField('Poster image', 'poster_path', $video['poster_path'] ?? '', 'image/*', 'video', 'shown before playback'); ?>
        </div>
    </fieldset>

    <fieldset class="rounded-xl border border-white/10 p-5">
        <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red">Audio tracks</legend>
        <div class="space-y-6">
            <?php foreach ($video['tracks'] ?? [] as $t): ?>
                <div class="grid gap-3 sm:grid-cols-[3rem,1fr] sm:items-start">
                    <span class="pt-2 text-xs uppercase text-white/40"><?= esc($t['locale']) ?></span>
                    <div class="space-y-3">
                        <input type="text" name="tracks[<?= (int) $t['id'] ?>][label]" value="<?= esc($t['label']) ?>" placeholder="Label" class="<?= $inputCls ?>">
                        <?php $fileField('Audio file', 'tracks[' . (int) $t['id'] . '][audio_path]', $t['audio_path'], 'audio/*', 'audio', 'MP3/AAC'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <fieldset class="rounded-xl border border-white/10 p-5">
        <legend class="px-2 text-xs font-semibold uppercase tracking-widest text-brand-red">Subtitles (WebVTT)</legend>
        <div class="space-y-6">
            <?php foreach ($video['subtitles'] ?? [] as $s): ?>
                <div class="grid gap-3 sm:grid-cols-[3rem,1fr] sm:items-start">
                    <span class="pt-2 text-xs uppercase text-white/40"><?= esc($s['locale']) ?></span>
                    <?php $fileField('VTT file', 'subs[' . (int) $s['id'] . '][vtt_path]', $s['vtt_path'], '.vtt,text/vtt', 'subtitles', '.vtt'); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <button class="btn-brand">Save</button>
</form>
<?= $this->endSection() ?>
