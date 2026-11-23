<?php
/** One product in a grid. @var \SiteRuntime\View $view @var array $product */
$p = $product;
$custom = $view->partial('product-card', ['product' => $p]);
if ($custom !== '') {
    echo $custom;
    return;
}
$img = $view->img($p['image'] ?? '');
?>
<article class="wbshop-card<?= $p['in_stock'] ? '' : ' is-out' ?>">
  <a class="wbshop-card-img" href="<?= $view->e($view->productUrl($p)) ?>">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="<?= $view->e($p['name']) ?>" loading="lazy"><?php endif; ?>
    <?php if (!$p['in_stock']): ?><span class="wbshop-flag is-muted">Sold out</span><?php elseif (($p['badge'] ?? '') !== ''): ?><span class="wbshop-flag"><?= $view->e($p['badge']) ?></span><?php elseif ($p['discount_pct'] > 0): ?><span class="wbshop-flag">−<?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
  </a>
  <div class="wbshop-card-body">
    <?php if (($p['category_name'] ?? '') !== ''): ?><span class="wbshop-card-cat"><?= $view->e($p['category_name']) ?></span><?php endif; ?>
    <h3 class="wbshop-card-title"><a href="<?= $view->e($view->productUrl($p)) ?>"><?= $view->e($p['name']) ?></a></h3>
    <div class="wbshop-price"><?php if ($p['price_from']): ?><small>From</small> <?php endif; ?><strong><?= $view->e($view->money($p['display_price'])) ?></strong>
      <?php if ((int) ($p['compare_price'] ?? 0) > $p['display_price']): ?><s><?= $view->e($view->money($p['compare_price'])) ?></s><?php endif; ?></div>
  </div>
</article>
