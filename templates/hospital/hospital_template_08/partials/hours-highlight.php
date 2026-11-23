<?php if ($label !== '' || $value !== ''): ?>
<div class="vga-hours-highlight">
  <span class="vga-pulse" aria-hidden="true"></span>
  <span class="vga-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?= $view->e($value) ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn vga-btn vga-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
