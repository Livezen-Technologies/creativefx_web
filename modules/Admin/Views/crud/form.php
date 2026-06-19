<?php
helper(['norlanka', 'form']);
$this->extend('Modules\Admin\Views\layout');
$inputCls = 'w-full rounded-lg border border-white/15 bg-black/40 px-3 py-2 text-sm focus:border-brand-red focus:outline-none';
$action   = $row ? site_url('admin/' . $route . '/' . $row['id']) : site_url('admin/' . $route);
?>
<?= $this->section('content') ?>
<form method="post" action="<?= $action ?>" enctype="multipart/form-data" class="max-w-2xl space-y-6">
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
