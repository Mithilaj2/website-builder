<?php if ($label !== '' || $value !== ''): ?>
<div class="ihp-hours-highlight">
  <span class="ihp-pulse" aria-hidden="true"></span>
  <span class="ihp-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?= $view->e($value) ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn ihp-btn ihp-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
