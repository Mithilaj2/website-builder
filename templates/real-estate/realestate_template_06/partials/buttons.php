<?php
$primary = $primary ?? [];
$secondary = $secondary ?? [];
$secondaryClass = $secondaryClass ?? 'hhv-btn-ghost';
$extra = $extra ?? '';
if (!$view->hasLink($primary) && !$view->hasLink($secondary)) {
    return;
}
?>
<div class="hhv-actions<?= $extra !== '' ? ' ' . $view->e($extra) : '' ?>">
  <?php if ($view->hasLink($primary)): ?><a class="btn hhv-btn hhv-btn-primary" href="<?= $view->href($primary) ?>"<?= $view->linkAttrs($primary) ?>><?= $view->e($primary['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
  <?php if ($view->hasLink($secondary)): ?><a class="btn hhv-btn <?= $view->e($secondaryClass) ?>" href="<?= $view->href($secondary) ?>"<?= $view->linkAttrs($secondary) ?>><?= $view->e($secondary['text']) ?></a><?php endif; ?>
</div>
