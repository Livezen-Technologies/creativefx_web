<?php
helper('norlanka');
$this->extend('Modules\Core\Views\layouts\main');
?>
<?= $this->section('content') ?>
<?php
// Full-bleed CMS renderer: every block partial is a self-contained <section>
// and owns its own layout. The first block of each page is normally a
// `pagehero` which provides the top padding for the fixed header.
foreach ($page['sections'] ?? [] as $section):
    foreach ($section['blocks'] ?? [] as $block):
        $type    = preg_replace('/[^a-z0-9_]/i', '', $block['type'] ?? 'richtext');
        $content = json_decode($block['content'] ?? '[]', true) ?: [];
        $partial = 'Modules\Site\Views\cms\blocks\\' . $type;
        try {
            echo view($partial, ['content' => $content], ['saveData' => true]);
        } catch (\Throwable $e) {
            echo view('Modules\Site\Views\cms\blocks\richtext', ['content' => $content], ['saveData' => true]);
        }
    endforeach;
endforeach;
?>
<?= $this->endSection() ?>
