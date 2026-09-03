<?php
helper('norlanka');

/**
 * The shell every message from this site uses.
 *
 * Table-based and inline-styled, because that is still what mail clients
 * render reliably — a stylesheet in <head> is stripped by several of them, and
 * a grid is understood by almost none. Kept in one place so a change to the
 * Authority's mail identity is one edit rather than four.
 *
 * @var string $heading
 * @var string $body     already-escaped HTML
 */
$site = setting('site_name', 'Tea Small Holdings Development Authority');
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title><?= esc($heading) ?></title></head>
<body style="margin:0;padding:0;background:#f4f6f4;font-family:'Noto Sans',Arial,Helvetica,sans-serif;color:#17231d">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f4;padding:24px 12px">
  <tr><td align="center">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;background:#ffffff;border:1px solid #d6e0da;border-radius:12px">
      <tr><td style="padding:20px 28px;border-bottom:1px solid #d6e0da">
        <p style="margin:0;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#0F6B45"><?= esc($site) ?></p>
      </td></tr>
      <tr><td style="padding:28px">
        <h1 style="margin:0 0 16px;font-size:19px;line-height:1.35;color:#17231d"><?= esc($heading) ?></h1>
        <?= $body ?>
      </td></tr>
      <tr><td style="padding:18px 28px;border-top:1px solid #d6e0da">
        <p style="margin:0;font-size:12px;line-height:1.6;color:#5b6b62">
          <?= esc($site) ?><br>
          <?= esc(setting('address', '', 'contact')) ?>
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
