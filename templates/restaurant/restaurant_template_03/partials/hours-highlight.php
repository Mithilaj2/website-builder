<?php if ($label !== '' || $value !== ''): ?>
<div class="atr-hours-highlight">
  <span class="atr-pulse" aria-hidden="true"></span>
  <span class="atr-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?php if ($emergency === '' && preg_match('/^\+?[0-9][0-9 ()-]{6,}$/', $value)): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $value)) ?>"><?= $view->e($value) ?></a><?php else: ?><?= $view->e($value) ?><?php endif; ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn atr-btn atr-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
