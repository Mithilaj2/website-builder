<?php
if (!$view->shopEnabled() || ($view->theme['header_style'] ?? '') === 'minimal') {
    return;
}
$messages = [];
$free = (int) $view->setting('shop', 'free_shipping_above', 0);
if ($free > 0) {
    $messages[] = 'Free delivery on orders above ' . $view->money($free);
}
if ($view->setting('shop', 'cod_enabled', false)) {
    $messages[] = 'Cash on delivery available';
}
if ((string) $view->site('tagline') !== '') {
    $messages[] = (string) $view->site('tagline');
}
if (!$messages) {
    return;
}
?>
<div class="jts-announce" aria-label="Announcements">
  <div class="container"><p><?php foreach ($messages as $i => $msg): ?><?= $i ? '<i class="d-none d-md-inline" aria-hidden="true">•</i>' : '' ?><span<?= $i ? ' class="d-none d-md-inline"' : '' ?>><?= $view->e($msg) ?></span><?php endforeach; ?></p></div>
</div>
