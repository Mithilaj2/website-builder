<?php if ($label !== '' || $value !== ''): ?>
<div class="gft-hours-highlight">
  <span class="gft-pulse" aria-hidden="true"></span>
  <span class="gft-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?= $view->e($value) ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn gft-btn gft-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
