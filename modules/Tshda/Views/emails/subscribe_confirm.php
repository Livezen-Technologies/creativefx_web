<?php
helper(['norlanka', 'url']);

ob_start(); ?>
<p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#3b4a42">
  Somebody — we hope you — asked to receive alerts from the Tea Small Holdings Development Authority at this address.
</p>
<p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#3b4a42">
  Confirm it and we will start sending them. If it was not you, ignore this message: nothing is sent until the link below is followed, and the address is removed on its own.
</p>
<p style="margin:0 0 24px">
  <a href="<?= esc($confirmUrl, 'attr') ?>"
     style="display:inline-block;background:#0F6B45;color:#ffffff;text-decoration:none;font-weight:600;font-size:14px;padding:12px 24px;border-radius:999px">
    Confirm my subscription
  </a>
</p>
<p style="margin:0;font-size:12px;line-height:1.6;color:#5b6b62">
  If the button does not work, copy this address into your browser:<br>
  <span style="word-break:break-all;color:#0F6B45"><?= esc($confirmUrl) ?></span>
</p>
<?php $body = ob_get_clean();

echo view('Modules\Tshda\Views\emails\_layout', [
    'heading' => 'Confirm your alerts',
    'body'    => $body,
], ['saveData' => false]);
