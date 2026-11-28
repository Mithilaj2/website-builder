<?php
$menu = $view->menu('header');
$shopOn = $view->shopEnabled();
$cats = $shopOn ? $view->productCategories() : [];
$isShop = ($view->page['slug'] ?? '') === 'shop' && (int) ($view->page['id'] ?? 0) === 0;
$lcfg = $view->listingConfig();
$ltypes = $lcfg ? $view->listingCategories() : [];
$lslug = (string) ($view->page['slug'] ?? '');
$onListings = $lcfg && (int) ($view->page['id'] ?? 0) === 0 && ($lslug === $lcfg['path'] || str_starts_with($lslug, $lcfg['path'] . '/'));
$lfor = $lcfg['attributes']['listing_for']['options'] ?? [];
?>
<ul class="navbar-nav <?= $view->e($class ?? 'ms-auto') ?> align-items-lg-center">
  <?php foreach ($menu as $i => $item): ?>
    <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
    <?php if ($i === 0 && $shopOn): ?>
      <li class="nav-item dropdown gft-shop-nav">
        <a class="nav-link<?= $isShop ? ' active' : '' ?>" href="<?= $view->e($view->shopUrl()) ?>"<?= $isShop ? ' aria-current="page"' : '' ?>>Shop</a>
        <?php if ($cats): ?>
          <button class="gft-drop-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Shop categories"><i class="bi bi-chevron-down"></i></button>
          <ul class="dropdown-menu gft-dropdown">
            <?php foreach ($cats as $c): ?><li><a class="dropdown-item" href="<?= $view->e($view->categoryUrl($c)) ?>"><?= $view->e($c['name']) ?></a></li><?php endforeach; ?>
            <li><hr class="dropdown-divider"></li><li><a class="dropdown-item fw-semibold" href="<?= $view->e($view->shopUrl()) ?>">All products</a></li>
          </ul>
        <?php endif; ?>
      </li>
    <?php endif; ?>
    <?php if ($i === 0 && $lcfg): ?>
      <li class="nav-item dropdown gft-shop-nav">
        <a class="nav-link<?= $onListings ? ' active' : '' ?>" href="<?= $view->e($view->listingsUrl()) ?>"<?= $onListings ? ' aria-current="page"' : '' ?>><?= $view->e($lcfg['menu_label']) ?></a>
        <?php if ($ltypes): ?>
          <button class="gft-drop-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= $view->e($lcfg['category_label']) ?>"><i class="bi bi-chevron-down"></i></button>
          <ul class="dropdown-menu gft-dropdown">
            <?php foreach ($ltypes as $t): ?><li><a class="dropdown-item" href="<?= $view->e($view->listingsUrl(['c' => $t['slug']])) ?>"><?= $view->e($t['name']) ?></a></li><?php endforeach; ?>
            <?php if (count($lfor) > 1): ?><li><hr class="dropdown-divider"></li><?php foreach ($lfor as $o): ?><li><a class="dropdown-item" href="<?= $view->e($view->listingsUrl(['a' => ['listing_for' => $o]])) ?>">For <?= $view->e(strtolower($o)) ?></a></li><?php endforeach; ?><?php endif; ?>
            <li><hr class="dropdown-divider"></li><li><a class="dropdown-item fw-semibold" href="<?= $view->e($view->listingsUrl()) ?>">All <?= $view->e(strtolower($lcfg['plural'])) ?></a></li>
          </ul>
        <?php endif; ?>
      </li>
    <?php endif; ?>
  <?php endforeach; ?>
</ul>
