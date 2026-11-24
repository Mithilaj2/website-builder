<?php
$primary = $primary ?? [];
$secondary = $secondary ?? [];
$secondaryClass = $secondaryClass ?? 'aur-btn-ghost';
$extra = $extra ?? '';
if (!$view->hasLink($primary) && !$view->hasLink($secondary)) {
    return;
}
?>
<div class="aur-actions<?= $extra !== '' ? ' ' . $view->e($extra) : '' ?>">
  <?php if ($view->hasLink($primary)): ?><a class="btn aur-btn aur-btn-primary" href="<?= $view->href($primary) ?>"<?= $view->linkAttrs($primary) ?>><?= $view->e($primary['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
  <?php if ($view->hasLink($secondary)): ?><a class="btn aur-btn <?= $view->e($secondaryClass) ?>" href="<?= $view->href($secondary) ?>"<?= $view->linkAttrs($secondary) ?>><?= $view->e($secondary['text']) ?></a><?php endif; ?>
</div>
