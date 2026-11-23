<?php
$align = $align ?? 'center';
$eyebrow = (string) ($data['eyebrow'] ?? '');
$heading = (string) ($data['heading'] ?? '');
$description = (string) ($data['description'] ?? '');
if ($eyebrow === '' && $heading === '' && $description === '') {
    return;
}
?>
<div class="lyc-head lyc-head-<?= $view->e($align) ?>">
  <?php if ($eyebrow !== ''): ?><span class="lyc-eyebrow"><?= $view->e($eyebrow) ?></span><?php endif; ?>
  <?php if ($heading !== ''): ?><h2 class="lyc-title"><?= $view->e($heading) ?></h2><?php endif; ?>
  <?php if ($description !== ''): ?><p class="lyc-lead"><?= $view->text($description) ?></p><?php endif; ?>
</div>
