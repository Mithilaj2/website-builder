<?php if ($label !== '' || $value !== ''): ?>
<div class="bhl-hours-highlight">
  <span class="bhl-pulse" aria-hidden="true"></span>
  <span class="bhl-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?= $view->e($value) ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn bhl-btn bhl-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
