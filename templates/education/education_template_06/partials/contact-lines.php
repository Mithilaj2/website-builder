<ul class="cfa-footer-contact">
  <?php if ($view->site('address') !== ''): ?><li><i class="bi bi-geo-alt"></i><span><?= $view->text($view->site('address')) ?></span></li><?php endif; ?>
  <?php if ($view->site('phone') !== ''): ?><li><i class="bi bi-telephone"></i><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))) ?>"><?= $view->e($view->site('phone')) ?></a></li><?php endif; ?>
  <?php if ($view->site('email') !== ''): ?><li><i class="bi bi-envelope"></i><a href="mailto:<?= $view->e($view->site('email')) ?>"><?= $view->e($view->site('email')) ?></a></li><?php endif; ?>
  <?php if ($view->site('hours') !== ''): ?><li><i class="bi bi-clock"></i><span><?= $view->e($view->site('hours')) ?></span></li><?php endif; ?>
</ul>
