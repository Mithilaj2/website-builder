<?php
$menu = $view->menu('header');
$logo = $view->logo();
$shopOn = $view->shopEnabled();
$cats = $shopOn ? $view->productCategories() : [];
$count = $view->cartCount();
$free = (int) $view->setting('shop', 'free_shipping_above', 0);
$messages = [];
if ($shopOn && $free > 0) {
    $messages[] = 'Free delivery on orders above ' . $view->money($free);
}
if ($shopOn && $view->setting('shop', 'cod_enabled', false)) {
    $messages[] = 'Cash on delivery available';
}
if ((string) $view->site('tagline') !== '') {
    $messages[] = (string) $view->site('tagline');
}
$isShop = ($view->page['slug'] ?? '') === 'shop' && (int) ($view->page['id'] ?? 0) === 0;
?>
<?php if (($view->theme['header_style'] ?? '') !== 'minimal' && $messages): ?>
<div class="trm-announce" aria-label="Announcements">
  <div class="trm-announce-track">
    <?php for ($i = 0; $i < 2; $i++): ?><span<?= $i ? ' aria-hidden="true"' : '' ?>><?php foreach ($messages as $msg): ?><b><?= $view->e($msg) ?></b><i aria-hidden="true">✦</i><?php endforeach; ?></span><?php endfor; ?>
  </div>
</div>
<?php endif; ?>
<header class="trm-header" data-trm-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#trmNav" aria-controls="trmNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <a class="navbar-brand trm-brand" href="<?= $view->e($view->url('')) ?>">
        <?php if ($logo !== ''): ?><img src="<?= $logo ?>" alt="<?= $view->e($view->site('name')) ?>"><?php else: ?><span class="trm-brand-name"><?= $view->e($view->site('name', 'Store')) ?></span><?php endif; ?>
      </a>
      <div class="trm-icons order-lg-3">
        <?php if ($shopOn): ?>
          <a class="trm-icon d-none d-sm-inline-flex" href="<?= $view->e($view->shopUrl()) ?>#wbshop-q" aria-label="Search products"><i class="bi bi-search"></i></a>
          <a class="trm-icon" href="<?= $view->e($view->shopUrl('cart')) ?>" aria-label="Cart, <?= $count ?> item<?= $count === 1 ? '' : 's' ?>"><i class="bi bi-bag"></i><?php if ($count > 0): ?><span class="trm-count"><?= $count ?></span><?php endif; ?></a>
        <?php endif; ?>
      </div>
      <div class="collapse navbar-collapse order-lg-2" id="trmNav">
        <ul class="navbar-nav mx-auto align-items-lg-center">
          <?php foreach ($menu as $i => $item): ?>
            <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
            <?php if ($i === 0 && $shopOn): ?>
              <li class="nav-item dropdown trm-shop-nav">
                <a class="nav-link<?= $isShop ? ' active' : '' ?>" href="<?= $view->e($view->shopUrl()) ?>">Shop</a>
                <?php if ($cats): ?>
                  <button class="trm-drop-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Shop categories"><i class="bi bi-chevron-down"></i></button>
                  <ul class="dropdown-menu trm-dropdown">
                    <?php foreach ($cats as $c): ?><li><a class="dropdown-item" href="<?= $view->e($view->categoryUrl($c)) ?>"><?= $view->e($c['name']) ?></a></li><?php endforeach; ?>
                    <li><hr class="dropdown-divider"></li><li><a class="dropdown-item fw-semibold" href="<?= $view->e($view->shopUrl()) ?>">All products</a></li>
                  </ul>
                <?php endif; ?>
              </li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>
