<div class="atr-menu-foot">
  <ul class="atr-menu-legend" aria-label="Key">
    <li><span class="atr-diet is-veg" aria-hidden="true"></span>Vegetarian</li>
    <li><span class="atr-diet is-nonveg" aria-hidden="true"></span>Non-vegetarian</li>
    <li><i class="bi bi-fire" aria-hidden="true"></i>Spice level</li>
  </ul>
  <?php if (($data['note'] ?? '') !== ''): ?><p class="atr-menu-note"><?= $view->e($data['note']) ?></p><?php endif; ?>
  <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="btn atr-btn atr-btn-ghost" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
</div>
