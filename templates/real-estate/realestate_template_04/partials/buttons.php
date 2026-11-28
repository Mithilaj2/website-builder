<?php
$primary = $primary ?? [];
$secondary = $secondary ?? [];
$secondaryClass = $secondaryClass ?? 'mrs-btn-ghost';
$extra = $extra ?? '';
if (!$view->hasLink($primary) && !$view->hasLink($secondary)) {
    return;
}
?>
<div class="mrs-actions<?= $extra !== '' ? ' ' . $view->e($extra) : '' ?>">
  <?php if ($view->hasLink($primary)): ?><a class="btn mrs-btn mrs-btn-primary" href="<?= $view->href($primary) ?>"<?= $view->linkAttrs($primary) ?>><?= $view->e($primary['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
  <?php if ($view->hasLink($secondary)): ?><a class="btn mrs-btn <?= $view->e($secondaryClass) ?>" href="<?= $view->href($secondary) ?>"<?= $view->linkAttrs($secondary) ?>><?= $view->e($secondary['text']) ?></a><?php endif; ?>
</div>
