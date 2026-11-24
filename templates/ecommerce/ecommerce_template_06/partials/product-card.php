<?php
$p = $product ?? [];
$img = $view->img($p['image'] ?? '');
$alt = $view->img(($p['gallery'] ?? [])[0] ?? '');
$inStock = !empty($p['in_stock']);
$hasOptions = (int) ($p['variant_count'] ?? 0) > 0;
$quick = $inStock && !$hasOptions && $view->shopToken() !== '';
?>
<article class="aur-product aur-product-overlay<?= $inStock ? '' : ' is-out' ?>">
  <div class="aur-product-media">
    <a href="<?= $view->e($view->productUrl($p)) ?>" class="aur-product-img" tabindex="-1" aria-hidden="true">
      <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
      <?php if ($alt !== ''): ?><img class="aur-product-alt" src="<?= $alt ?>" alt="" loading="lazy"><?php endif; ?>
    </a>
    <?= $view->partial('product-flag', ['p' => $p, 'inStock' => $inStock]) ?>
    <?php if ($quick): ?>
      <form class="aur-quick" method="post" action="<?= $view->e($view->shopUrl('cart')) ?>">
        <input type="hidden" name="_t" value="<?= $view->e($view->shopToken()) ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="qty" value="1">
        <button type="submit" aria-label="Add <?= $view->e($p['name'] ?? '') ?> to cart"><i class="bi bi-bag-plus"></i><span>Add to cart</span></button>
      </form>
    <?php elseif ($inStock && $hasOptions): ?>
      <a class="aur-quick aur-quick-link" href="<?= $view->e($view->productUrl($p)) ?>"><i class="bi bi-sliders"></i><span>Choose options</span></a>
    <?php endif; ?>
  </div>
  <div class="aur-product-body">
    <?php if (($p['category_name'] ?? '') !== ''): ?><span class="aur-product-cat"><?= $view->e($p['category_name']) ?></span><?php endif; ?>
    <h3 class="aur-product-title"><a href="<?= $view->e($view->productUrl($p)) ?>"><?= $view->e($p['name'] ?? '') ?></a></h3>
    <?= $view->partial('product-price', ['p' => $p]) ?>
  </div>
</article>
