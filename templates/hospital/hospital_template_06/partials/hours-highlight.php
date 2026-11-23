<?php if ($label !== '' || $value !== ''): ?>
<div class="psd-hours-highlight">
  <span class="psd-pulse" aria-hidden="true"></span>
  <span class="psd-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?= $view->e($value) ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn psd-btn psd-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
