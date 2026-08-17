<?php
helper(['norlanka', 'form']);
$this->extend('Modules\Admin\Views\layout');
$inputCls = 'w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';
$action   = $row ? site_url('admin/' . $route . '/' . $row['id']) : site_url('admin/' . $route);
?>
<?= $this->section('content') ?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="max-w-3xl space-y-6">
    <?= csrf_field() ?>
    <?php foreach ($fields as $f):
        $name  = $f['name'];
        $type  = $f['type'] ?? 'text';
        $label = $f['label'] ?? $name;
        $ro    = ! empty($f['readonly']); ?>
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-widest text-white/50"><?= esc($label) ?></label>

            <?php if ($type === 'static'): $v = $row[$name] ?? ''; ?>
                <div class="text-white/80"><?= esc(is_scalar($v) ? (string) $v : '') ?></div>

            <?php elseif ($type === 'locale'):
                $map = [];
                $raw = $row[$name] ?? null;
                if (is_string($raw)) { $d = json_decode($raw, true); if (is_array($d)) { $map = $d; } }
                foreach ($locales as $l):
                    $val = old($name . '_' . $l) ?? ($map[$l] ?? ''); ?>
                    <div class="mb-2 flex items-center gap-2">
                        <span class="w-8 text-xs uppercase text-white/40"><?= esc($l) ?></span>
                        <input type="text" name="<?= esc($name . '_' . $l, 'attr') ?>" value="<?= esc($val) ?>" class="<?= $inputCls ?>">
                    </div>
                <?php endforeach; ?>

            <?php elseif ($type === 'locale_textarea'):
                $map = [];
                $raw = $row[$name] ?? null;
                if (is_string($raw)) { $d = json_decode($raw, true); if (is_array($d)) { $map = $d; } }
                foreach ($locales as $l):
                    $val = old($name . '_' . $l) ?? ($map[$l] ?? ''); ?>
                    <div class="mb-2 flex items-start gap-2">
                        <span class="w-8 pt-2 text-xs uppercase text-white/40"><?= esc($l) ?></span>
                        <textarea name="<?= esc($name . '_' . $l, 'attr') ?>" rows="<?= $l === 'en' ? 10 : 4 ?>" class="<?= $inputCls ?>"><?= esc($val) ?></textarea>
                    </div>
                <?php endforeach; ?>

            <?php elseif ($type === 'locale_richtext'):
                // Tabbed per-locale rich text editors (Quill). Each locale keeps
                // a hidden textarea that carries the HTML; admin.js mounts the
                // editor and syncs on change.
                $map = [];
                $raw = $row[$name] ?? null;
                if (is_string($raw)) { $d = json_decode($raw, true); if (is_array($d)) { $map = $d; } } ?>
                <div x-data="{ tab: '<?= esc($locales[0] ?? 'en') ?>' }">
                    <div class="mb-2 flex gap-1">
                        <?php foreach ($locales as $l): ?>
                            <button type="button" @click="tab = '<?= esc($l) ?>'"
                                    :class="tab === '<?= esc($l) ?>' ? 'bg-brand-red text-brand-ink' : 'bg-white/5 text-white/50 hover:text-white'"
                                    class="rounded-md px-3 py-1 text-xs font-semibold uppercase tracking-widest transition"><?= esc($l) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <?php foreach ($locales as $l):
                        $val = old($name . '_' . $l) ?? ($map[$l] ?? ''); ?>
                        <div x-show="tab === '<?= esc($l) ?>'" data-richtext <?= ! empty($f['compact']) ? 'data-compact' : '' ?> data-placeholder="Write the <?= esc(strtoupper($l)) ?> version…">
                            <textarea name="<?= esc($name . '_' . $l, 'attr') ?>" class="hidden"><?= esc($val) ?></textarea>
                            <div class="rt-editor"></div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif ($type === 'image' || $type === 'file'):
                // Drag & drop upload zone backed by the media library; stores the
                // uploaded file URL in a plain input (still hand-editable).
                // 'file' takes a custom `accept` (e.g. '.glb,.gltf' or '.pdf').
                $val    = old($name) ?? ($row[$name] ?? '');
                $accept = $f['accept'] ?? ($type === 'image' ? 'image/*' : '');
                $fid    = 'f_' . $name; ?>
                <input type="text" id="<?= esc($fid, 'attr') ?>" name="<?= esc($name, 'attr') ?>" value="<?= esc($val) ?>"
                       placeholder="/media/…" class="<?= $inputCls ?> mb-2">
                <label data-dropzone="<?= esc($fid, 'attr') ?>" data-folder="<?= esc($f['folder'] ?? 'uploads', 'attr') ?>"
                       class="block p-5 text-center">
                    <input type="file" <?= $accept !== '' ? 'accept="' . esc($accept, 'attr') . '"' : '' ?> class="hidden">
                    <div class="dz-preview mb-2 flex justify-center"></div>
                    <p class="text-sm text-white/60">Drag &amp; drop a file here, or <span class="font-semibold text-brand-red">browse</span></p>
                    <p class="dz-status mt-1 text-xs text-white/40"><?= $type === 'image' ? 'Images are optimized to WebP automatically.' : 'Stored in the media library.' ?></p>
                </label>

            <?php elseif ($type === 'gallery'):
                // Multi-image gallery: hidden textarea carries a JSON array of
                // paths; admin.js renders sortable thumbs with add/remove.
                $val = old($name) ?? ($row[$name] ?? '[]'); ?>
                <div data-gallery data-folder="<?= esc($f['folder'] ?? 'uploads', 'attr') ?>">
                    <textarea name="<?= esc($name, 'attr') ?>" class="hidden"><?= esc(is_string($val) ? $val : json_encode($val)) ?></textarea>
                    <div class="gal-grid"></div>
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="gal-add-lib rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-white/70 hover:border-white/40">Add from library</button>
                        <label class="gal-add-up cursor-pointer rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-white/70 hover:border-white/40">
                            Upload images…
                            <input type="file" accept="image/*" multiple class="hidden">
                        </label>
                        <span class="gal-status self-center text-xs text-white/40"></span>
                    </div>
                </div>

            <?php elseif ($type === 'list' || $type === 'pairs'):
                // Item-list editors: the hidden textarea carries the JSON the
                // model already stores; admin.js renders add/remove/reorder rows.
                // 'list' = array of strings; 'pairs' = array of {label, value}.
                $val = old($name) ?? ($row[$name] ?? '[]'); ?>
                <div data-<?= $type ?> <?= ! empty($f['pair_labels']) ? 'data-pair-labels="' . esc(implode('|', $f['pair_labels']), 'attr') . '"' : '' ?>>
                    <textarea name="<?= esc($name, 'attr') ?>" class="hidden"><?= esc(is_string($val) ? $val : json_encode($val, JSON_UNESCAPED_UNICODE)) ?></textarea>
                    <div class="il-rows"></div>
                    <button type="button" class="il-add mt-2 rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-white/70 hover:border-white/40">+ Add <?= esc(strtolower($f['item_label'] ?? 'item')) ?></button>
                </div>

            <?php elseif ($type === 'textarea'): $val = old($name) ?? ($row[$name] ?? ''); ?>
                <textarea name="<?= esc($name, 'attr') ?>" rows="4" <?= $ro ? 'readonly' : '' ?> class="<?= $inputCls ?>"><?= esc($val) ?></textarea>

            <?php elseif ($type === 'select'): $val = old($name) ?? ($row[$name] ?? ''); ?>
                <select name="<?= esc($name, 'attr') ?>" class="<?= $inputCls ?>">
                    <?php foreach (($f['options'] ?? []) as $ov => $ol): ?>
                        <option value="<?= esc($ov, 'attr') ?>" <?= (string) $val === (string) $ov ? 'selected' : '' ?>><?= esc($ol) ?></option>
                    <?php endforeach; ?>
                </select>

            <?php elseif ($type === 'checkbox'): $val = old($name) ?? ($row[$name] ?? 0); ?>
                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" name="<?= esc($name, 'attr') ?>" value="1" <?= $val ? 'checked' : '' ?>>
                    <span class="text-sm text-white/70"><?= esc($f['help'] ?? 'Enabled') ?></span>
                </label>

            <?php else: $val = old($name) ?? ($row[$name] ?? ''); ?>
                <input type="<?= $type === 'email' ? 'email' : ($type === 'number' ? 'number' : 'text') ?>"
                       name="<?= esc($name, 'attr') ?>" value="<?= esc($val) ?>" <?= $ro ? 'readonly' : '' ?> class="<?= $inputCls ?>">
            <?php endif; ?>

            <?php if (! empty($f['help']) && $type !== 'checkbox'): ?>
                <p class="mt-1 text-xs text-white/40"><?= esc($f['help']) ?></p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="flex gap-3 pt-2">
        <button class="btn-brand">Save</button>
        <a href="<?= site_url('admin/' . $route) ?>" class="btn-ghost">Cancel</a>
    </div>
</form>
<?= $this->endSection() ?>
