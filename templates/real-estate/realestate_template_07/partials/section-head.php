<?php
$align = $align ?? 'center';
$eyebrow = (string) ($data['eyebrow'] ?? '');
$heading = (string) ($data['heading'] ?? '');
$description = (string) ($data['description'] ?? '');
if ($eyebrow === '' && $heading === '' && $description === '') {
    return;
}
?>
<div class="bwr-head bwr-head-<?= $view->e($align) ?>">
  <?php if ($eyebrow !== ''): ?><span class="bwr-eyebrow"><?= $view->e($eyebrow) ?></span><?php endif; ?>
  <?php if ($heading !== ''): ?><h2 class="bwr-title"><?= $view->e($heading) ?></h2><?php endif; ?>
  <?php if ($description !== ''): ?><p class="bwr-lead"><?= $view->text($description) ?></p><?php endif; ?>
</div>
