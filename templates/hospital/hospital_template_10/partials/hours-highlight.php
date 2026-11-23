<?php if ($label !== '' || $value !== ''): ?>
<div class="chi-hours-highlight">
  <span class="chi-pulse" aria-hidden="true"></span>
  <span class="chi-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?= $view->e($value) ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn chi-btn chi-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
