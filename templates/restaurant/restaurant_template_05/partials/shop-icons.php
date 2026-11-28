<?php if (!$view->shopEnabled()) { return; } $count = $view->cartCount(); ?>
<div class="eao-shop-icons<?= isset($class) ? ' ' . $view->e($class) : '' ?>">
  <a class="eao-shop-icon d-none d-sm-inline-flex" href="<?= $view->e($view->shopUrl()) ?>#wbshop-q" aria-label="Search products"><i class="bi bi-search"></i></a>
  <a class="eao-shop-icon" href="<?= $view->e($view->shopUrl('cart')) ?>" aria-label="Cart, <?= $count ?> item<?= $count === 1 ? '' : 's' ?>"><i class="bi bi-bag"></i><?php if ($count > 0): ?><span class="eao-cart-count"><?= $count ?></span><?php endif; ?></a>
</div>
