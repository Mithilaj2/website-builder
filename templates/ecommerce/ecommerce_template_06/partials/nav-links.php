<?php
$menu = $view->menu('header');
$shopOn = $view->shopEnabled();
$cats = $shopOn ? $view->productCategories() : [];
$isShop = ($view->page['slug'] ?? '') === 'shop' && (int) ($view->page['id'] ?? 0) === 0;
?>
<ul class="navbar-nav <?= $view->e($class ?? 'ms-auto') ?> align-items-lg-center">
  <?php foreach ($menu as $i => $item): ?>
    <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
    <?php if ($i === 0 && $shopOn): ?>
      <li class="nav-item dropdown aur-shop-nav">
        <a class="nav-link<?= $isShop ? ' active' : '' ?>" href="<?= $view->e($view->shopUrl()) ?>"<?= $isShop ? ' aria-current="page"' : '' ?>>Shop</a>
        <?php if ($cats): ?>
          <button class="aur-drop-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Shop categories"><i class="bi bi-chevron-down"></i></button>
          <ul class="dropdown-menu aur-dropdown">
            <?php foreach ($cats as $c): ?><li><a class="dropdown-item" href="<?= $view->e($view->categoryUrl($c)) ?>"><?= $view->e($c['name']) ?></a></li><?php endforeach; ?>
            <li><hr class="dropdown-divider"></li><li><a class="dropdown-item fw-semibold" href="<?= $view->e($view->shopUrl()) ?>">All products</a></li>
          </ul>
        <?php endif; ?>
      </li>
    <?php endif; ?>
  <?php endforeach; ?>
</ul>
