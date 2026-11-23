<?php
/* One product tile, used by the Products section and by the shop pages. */
$p = $product ?? [];
$img = $view->img($p['image'] ?? '');
$alt = $view->img(($p['gallery'] ?? [])[0] ?? '');
$quick = !empty($p['in_stock']) && (int) ($p['variant_count'] ?? 0) === 0 && $view->shopToken() !== '';
?>
<article class="trm-card<?= empty($p['in_stock']) ? ' is-out' : '' ?>">
  <div class="trm-card-media">
    <a href="<?= $view->e($view->productUrl($p)) ?>" class="trm-card-img" tabindex="-1" aria-hidden="true">
      <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
      <?php if ($alt !== ''): ?><img class="trm-card-alt" src="<?= $alt ?>" alt="" loading="lazy"><?php endif; ?>
    </a>
    <?php if (empty($p['in_stock'])): ?><span class="trm-flag is-dark">Sold out</span>
    <?php elseif (($p['badge'] ?? '') !== ''): ?><span class="trm-flag"><?= $view->e($p['badge']) ?></span>
    <?php elseif ((int) ($p['discount_pct'] ?? 0) > 0): ?><span class="trm-flag is-sale">−<?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
    <?php if ($quick): ?>
      <form class="trm-quick" method="post" action="<?= $view->e($view->shopUrl('cart')) ?>">
        <input type="hidden" name="_t" value="<?= $view->e($view->shopToken()) ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="qty" value="1">
        <button type="submit" aria-label="Add <?= $view->e($p['name'] ?? '') ?> to cart"><i class="bi bi-bag-plus"></i><span>Add to cart</span></button>
      </form>
    <?php elseif (!empty($p['in_stock']) && (int) ($p['variant_count'] ?? 0) > 0): ?>
      <a class="trm-quick trm-quick-link" href="<?= $view->e($view->productUrl($p)) ?>"><i class="bi bi-sliders"></i><span>Choose options</span></a>
    <?php endif; ?>
  </div>
  <div class="trm-card-body">
    <?php if (($p['category_name'] ?? '') !== ''): ?><span class="trm-card-cat"><?= $view->e($p['category_name']) ?></span><?php endif; ?>
    <h3 class="trm-card-title"><a href="<?= $view->e($view->productUrl($p)) ?>"><?= $view->e($p['name'] ?? '') ?></a></h3>
    <div class="trm-price"><?php if (!empty($p['price_from'])): ?><small>From</small><?php endif; ?><strong><?= $view->e($view->money($p['display_price'] ?? 0)) ?></strong><?php if ((int) ($p['compare_price'] ?? 0) > (int) ($p['display_price'] ?? 0)): ?><s><?= $view->e($view->money($p['compare_price'])) ?></s><?php endif; ?></div>
  </div>
</article>
