<?php if ($label !== '' || $value !== ''): ?>
<div class="fnl-hours-highlight">
  <span class="fnl-pulse" aria-hidden="true"></span>
  <span class="fnl-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?php if ($emergency === '' && preg_match('/^\+?[0-9][0-9 ()-]{6,}$/', $value)): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $value)) ?>"><?= $view->e($value) ?></a><?php else: ?><?= $view->e($value) ?><?php endif; ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn fnl-btn fnl-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
