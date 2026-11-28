<?php if (!$view->shopEnabled()) { return; } $count = $view->cartCount(); ?>
<div class="kkc-shop-icons<?= isset($class) ? ' ' . $view->e($class) : '' ?>">
  <a class="kkc-shop-icon d-none d-sm-inline-flex" href="<?= $view->e($view->shopUrl()) ?>#wbshop-q" aria-label="Search products"><i class="bi bi-search"></i></a>
  <a class="kkc-shop-icon" href="<?= $view->e($view->shopUrl('cart')) ?>" aria-label="Cart, <?= $count ?> item<?= $count === 1 ? '' : 's' ?>"><i class="bi bi-bag"></i><?php if ($count > 0): ?><span class="kkc-cart-count"><?= $count ?></span><?php endif; ?></a>
</div>
