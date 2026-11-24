<?php if ($label !== '' || $value !== ''): ?>
<div class="ttt-hours-highlight">
  <span class="ttt-pulse" aria-hidden="true"></span>
  <span class="ttt-hours-hl-text"><small><?= $view->e($label) ?></small><strong><?= $view->e($value) ?></strong></span>
  <?php if ($emergency !== ''): ?><a class="btn ttt-btn ttt-btn-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><i class="bi bi-telephone"></i><?= $view->e($emergency) ?></a><?php endif; ?>
</div>
<?php endif; ?>
