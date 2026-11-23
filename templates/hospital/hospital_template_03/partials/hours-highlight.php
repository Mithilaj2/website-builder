<?php if ($label !== '' || $value !== ''): ?>
<div class="scd-hours-highlight">
  <span class="scd-pulse" aria-hidden="true"></span>
  <span class="scd-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?= $view->e($value) ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn scd-btn scd-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
