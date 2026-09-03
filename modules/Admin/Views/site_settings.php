<?php
helper(['norlanka', 'url']);
$this->extend('Modules\Admin\Views\layout');

$inputCls = 'w-full rounded-lg border border-white/15 bg-black/30 px-3 py-2 text-sm text-white placeholder:text-white/25 focus:border-brand-red focus:outline-none';
$group    = $groups[$current];
?>
<?= $this->section('content') ?>

<?php // Groups as tabs rather than one long page: these are seven unrelated
      // subjects, and a single form of forty fields is one where nobody is sure
      // which Save button belongs to what. Each group saves on its own. ?>
<div class="mb-6 flex flex-wrap gap-1.5 border-b border-white/10 pb-4">
    <?php foreach ($groups as $key => $g): ?>
        <a href="<?= site_url('admin/site-settings/' . $key) ?>"
           class="rounded-lg px-3 py-1.5 text-sm transition <?= $key === $current
               ? 'bg-white/15 font-semibold text-white'
               : 'text-white/60 hover:bg-white/5 hover:text-white' ?>">
            <?= esc($g['label']) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if (session('errors')): ?>
    <div class="mb-5 rounded-lg border border-brand-red/40 bg-brand-red/10 px-4 py-3 text-sm" role="alert">
        <p class="mb-1 font-semibold text-brand-red">That could not be saved:</p>
        <ul class="list-inside list-disc space-y-0.5 text-white/80">
            <?php foreach ((array) session('errors') as $err): ?>
                <li><?= esc($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= site_url('admin/site-settings/' . $current) ?>" class="max-w-3xl space-y-5">
    <?= csrf_field() ?>

    <div class="mb-6">
        <h2 class="text-lg font-bold"><?= esc($group['label']) ?></h2>
        <p class="mt-1 text-sm leading-relaxed text-white/50"><?= esc($group['blurb']) ?></p>
    </div>

    <?php foreach ($group['fields'] as $f):
        $key   = $f['key'];
        $type  = $f['type'];
        $value = old($key) ?? ($values[$key] ?? '');
        $fid   = 'set_' . $key; ?>

        <div>
            <label for="<?= esc($fid, 'attr') ?>" class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-white/50">
                <?= esc($f['label']) ?>
            </label>

            <?php if ($type === 'checkbox'): ?>
                <label class="inline-flex items-center gap-2.5">
                    <input type="checkbox" id="<?= esc($fid, 'attr') ?>" name="<?= esc($key, 'attr') ?>" value="1" <?= $value === '1' ? 'checked' : '' ?>
                           class="h-4 w-4 rounded border-white/25 bg-black/30">
                    <span class="text-sm text-white/70"><?= esc($f['help'] ?? 'Enabled') ?></span>
                </label>

            <?php elseif ($type === 'textarea'): ?>
                <textarea id="<?= esc($fid, 'attr') ?>" name="<?= esc($key, 'attr') ?>" rows="3" class="<?= $inputCls ?>"
                          placeholder="<?= esc($f['placeholder'] ?? '', 'attr') ?>"><?= esc($value) ?></textarea>

            <?php elseif ($type === 'select'): ?>
                <select id="<?= esc($fid, 'attr') ?>" name="<?= esc($key, 'attr') ?>" class="<?= $inputCls ?>">
                    <?php foreach ($f['options'] as $ov => $ol): ?>
                        <option value="<?= esc($ov, 'attr') ?>" <?= (string) $value === (string) $ov ? 'selected' : '' ?>><?= esc($ol) ?></option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($type === 'color'): ?>
                <div class="flex items-center gap-3">
                    <input type="color" value="<?= esc($value !== '' ? $value : '#346142', 'attr') ?>"
                           oninput="document.getElementById('<?= esc($fid, 'attr') ?>').value = this.value"
                           class="h-9 w-12 cursor-pointer rounded border border-white/15 bg-transparent p-0.5">
                    <input type="text" id="<?= esc($fid, 'attr') ?>" name="<?= esc($key, 'attr') ?>" value="<?= esc($value) ?>"
                           placeholder="#346142" class="<?= $inputCls ?>">
                </div>

            <?php elseif ($type === 'image'):
                // Same dropzone the rest of the admin uses, so an upload here
                // goes through the media library's validation, WebP conversion
                // and duplicate detection rather than a second path of its own. ?>
                <?php if ($value !== ''): ?>
                    <div class="mb-2 flex items-center gap-3 rounded-lg border border-white/10 bg-black/20 p-2.5">
                        <img src="<?= esc(media_src($value), 'attr') ?>" alt=""
                             class="h-12 w-auto max-w-[8rem] rounded bg-white/5 object-contain">
                        <code class="min-w-0 flex-1 truncate text-xs text-white/45"><?= esc($value) ?></code>
                        <?php // Clearing the field and saving is the remove: the
                              // file stays in the media library, where it may be
                              // used by something else. ?>
                        <button type="button" onclick="document.getElementById('<?= esc($fid, 'attr') ?>').value = ''; this.closest('div').remove();"
                                class="shrink-0 rounded-lg border border-white/15 px-2.5 py-1 text-xs text-white/60 transition hover:border-brand-red hover:text-white">
                            Remove
                        </button>
                    </div>
                <?php endif; ?>
                <input type="text" id="<?= esc($fid, 'attr') ?>" name="<?= esc($key, 'attr') ?>" value="<?= esc($value) ?>"
                       placeholder="/media/…" class="<?= $inputCls ?> mb-2">
                <label data-dropzone="<?= esc($fid, 'attr') ?>" data-folder="branding" class="block cursor-pointer rounded-lg border border-dashed border-white/15 p-4 text-center transition hover:border-white/35">
                    <input type="file" accept="image/*" class="hidden">
                    <div class="dz-preview mb-2 flex justify-center"></div>
                    <p class="text-sm text-white/60">Drag &amp; drop an image, or <span class="font-semibold text-brand-red">browse</span></p>
                    <p class="dz-status mt-1 text-xs text-white/40">
                        <?= esc($f['recommend'] ?? 'Images are optimized automatically.') ?>
                    </p>
                </label>

            <?php else: ?>
                <input type="<?= in_array($type, ['email', 'url', 'tel', 'number'], true) ? esc($type, 'attr') : 'text' ?>"
                       id="<?= esc($fid, 'attr') ?>" name="<?= esc($key, 'attr') ?>" value="<?= esc($value) ?>"
                       placeholder="<?= esc($f['placeholder'] ?? '', 'attr') ?>" class="<?= $inputCls ?>">
            <?php endif; ?>

            <?php if (! empty($f['help']) && $type !== 'checkbox'): ?>
                <p class="mt-1.5 text-xs leading-relaxed text-white/40"><?= esc($f['help']) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="flex items-center gap-3 border-t border-white/10 pt-5">
        <button class="btn-brand">Save <?= esc(strtolower($group['label'])) ?></button>
        <a href="<?= site_url() ?>" target="_blank" rel="noopener" class="text-sm text-white/50 transition hover:text-white">View the site &rarr;</a>
    </div>
</form>

<?= $this->endSection() ?>
