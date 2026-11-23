<?php
$primary = $primary ?? [];
$secondary = $secondary ?? [];
$secondaryClass = $secondaryClass ?? 'rvp-btn-ghost';
$extra = $extra ?? '';
if (!$view->hasLink($primary) && !$view->hasLink($secondary)) {
    return;
}
?>
<div class="rvp-actions<?= $extra !== '' ? ' ' . $view->e($extra) : '' ?>">
  <?php if ($view->hasLink($primary)): ?><a class="btn rvp-btn rvp-btn-primary" href="<?= $view->href($primary) ?>"<?= $view->linkAttrs($primary) ?>><?= $view->e($primary['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
  <?php if ($view->hasLink($secondary)): ?><a class="btn rvp-btn <?= $view->e($secondaryClass) ?>" href="<?= $view->href($secondary) ?>"<?= $view->linkAttrs($secondary) ?>><?= $view->e($secondary['text']) ?></a><?php endif; ?>
</div>
