<?php
$align = $align ?? 'center';
$eyebrow = (string) ($data['eyebrow'] ?? '');
$heading = (string) ($data['heading'] ?? '');
$description = (string) ($data['description'] ?? '');
if ($eyebrow === '' && $heading === '' && $description === '') {
    return;
}
?>
<div class="crl-head crl-head-<?= $view->e($align) ?>">
  <?php if ($eyebrow !== ''): ?><span class="crl-eyebrow"><?= $view->e($eyebrow) ?></span><?php endif; ?>
  <?php if ($heading !== ''): ?><h2 class="crl-title"><?= $view->e($heading) ?></h2><?php endif; ?>
  <?php if ($description !== ''): ?><p class="crl-lead"><?= $view->text($description) ?></p><?php endif; ?>
</div>
