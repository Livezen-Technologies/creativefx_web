<?php
ob_start(); ?>
<p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#5b6b62">Your reference</p>
<p style="margin:0 0 18px;font-size:20px;font-weight:700;color:#0F6B45;font-family:'Courier New',monospace"><?= esc($reference) ?></p>
<p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#5b6b62">Your submission</p>
<p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#17231d"><?= esc($subject) ?></p>
<p style="margin:0 0 6px;font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#5b6b62">The Authority’s response</p>
<p style="margin:0;font-size:14px;line-height:1.6;color:#17231d"><?= nl2br(esc($response)) ?></p>
<?php $body = ob_get_clean();

echo view('Modules\Tshda\Views\emails\_layout', [
    'heading' => 'A response to your submission',
    'body'    => $body,
], ['saveData' => false]);
