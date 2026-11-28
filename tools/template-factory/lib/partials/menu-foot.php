<div class="{{p}}-menu-foot">
  <ul class="{{p}}-menu-legend" aria-label="Key">
    <li><span class="{{p}}-diet is-veg" aria-hidden="true"></span>Vegetarian</li>
    <li><span class="{{p}}-diet is-nonveg" aria-hidden="true"></span>Non-vegetarian</li>
    <li><i class="bi bi-fire" aria-hidden="true"></i>Spice level</li>
  </ul>
  <?php if (($data['note'] ?? '') !== ''): ?><p class="{{p}}-menu-note"><?= $view->e($data['note']) ?></p><?php endif; ?>
  <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="btn {{p}}-btn {{p}}-btn-ghost" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
</div>
