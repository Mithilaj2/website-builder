<?php $emergency = (string) $view->site('emergency_phone'); if ($emergency === '') { return; } ?>
<a class="bwr-emergency<?= isset($class) ? ' ' . $view->e($class) : '' ?>" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><span class="bwr-pulse" aria-hidden="true"></span><span>Emergency 24×7</span><b><?= $view->e($emergency) ?></b></a>
