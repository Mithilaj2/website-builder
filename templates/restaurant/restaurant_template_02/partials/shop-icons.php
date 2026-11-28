<?php if (!$view->shopEnabled()) { return; } $count = $view->cartCount(); ?>
<div class="mrc-shop-icons<?= isset($class) ? ' ' . $view->e($class) : '' ?>">
  <a class="mrc-shop-icon d-none d-sm-inline-flex" href="<?= $view->e($view->shopUrl()) ?>#wbshop-q" aria-label="Search products"><i class="bi bi-search"></i></a>
  <a class="mrc-shop-icon" href="<?= $view->e($view->shopUrl('cart')) ?>" aria-label="Cart, <?= $count ?> item<?= $count === 1 ? '' : 's' ?>"><i class="bi bi-bag"></i><?php if ($count > 0): ?><span class="mrc-cart-count"><?= $count ?></span><?php endif; ?></a>
</div>
