<?php
ob_start(); ?>
<p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#3b4a42"><?= esc($message) ?></p>
<p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#5b6b62">Your reference</p>
<p style="margin:0;font-size:20px;font-weight:700;color:#0F6B45;font-family:'Courier New',monospace"><?= esc($reference) ?></p>
<?php if (trim((string) $note) !== ''): ?>
  <p style="margin:20px 0 0;font-size:14px;line-height:1.6;color:#3b4a42"><?= nl2br(esc($note)) ?></p>
<?php endif; ?>
<?php $body = ob_get_clean();

echo view('Modules\Tshda\Views\emails\_layout', [
    'heading' => 'Your training application ' . $reference,
    'body'    => $body,
], ['saveData' => false]);
