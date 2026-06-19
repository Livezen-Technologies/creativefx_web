<?php
helper('norlanka');
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>
<div class="pt-28">
    <header class="container-x py-12">
        <h1 class="text-4xl font-bold sm:text-5xl"><?= esc(t_field($page['title'] ?? [])) ?></h1>
    </header>

    <?php foreach ($page['sections'] ?? [] as $section): ?>
        <section class="container-x py-6">
            <?php foreach ($section['blocks'] ?? [] as $block):
                $type    = preg_replace('/[^a-z0-9_]/i', '', $block['type'] ?? 'richtext');
                $content = json_decode($block['content'] ?? '[]', true) ?: [];
                $partial = 'Modules\Site\Views\cms\blocks\\' . $type;
                try {
                    echo view($partial, ['content' => $content], ['saveData' => true]);
                } catch (\Throwable $e) {
                    echo view('Modules\Site\Views\cms\blocks\richtext', ['content' => $content], ['saveData' => true]);
                }
            endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
