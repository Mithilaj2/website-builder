<?php
$p = $product ?? [];
$img = $view->img($p['image'] ?? '');
$alt = $view->img(($p['gallery'] ?? [])[0] ?? '');
$inStock = !empty($p['in_stock']);
$hasOptions = (int) ($p['variant_count'] ?? 0) > 0;
$quick = $inStock && !$hasOptions && $view->shopToken() !== '';
?>
<article class="vlt-product vlt-product-plain<?= $inStock ? '' : ' is-out' ?>">
  <a href="<?= $view->e($view->productUrl($p)) ?>" class="vlt-product-media vlt-product-img" tabindex="-1" aria-hidden="true">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
    <?php if ($alt !== ''): ?><img class="vlt-product-alt" src="<?= $alt ?>" alt="" loading="lazy"><?php endif; ?>
    <?= $view->partial('product-flag', ['p' => $p, 'inStock' => $inStock]) ?>
  </a>
  <div class="vlt-product-body">
    <?php if (($p['category_name'] ?? '') !== ''): ?><span class="vlt-product-cat"><?= $view->e($p['category_name']) ?></span><?php endif; ?>
    <h3 class="vlt-product-title"><a href="<?= $view->e($view->productUrl($p)) ?>"><?= $view->e($p['name'] ?? '') ?></a></h3>
    <?= $view->partial('product-price', ['p' => $p]) ?>
    <?php if ($quick): ?>
      <form class="vlt-add" method="post" action="<?= $view->e($view->shopUrl('cart')) ?>">
        <input type="hidden" name="_t" value="<?= $view->e($view->shopToken()) ?>"><input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="qty" value="1">
        <button type="submit" class="btn vlt-btn vlt-btn-ghost w-100" aria-label="Add <?= $view->e($p['name'] ?? '') ?> to cart"><i class="bi bi-bag-plus"></i>Add to cart</button>
      </form>
    <?php elseif ($inStock && $hasOptions): ?>
      <a class="btn vlt-btn vlt-btn-ghost w-100 vlt-add" href="<?= $view->e($view->productUrl($p)) ?>"><i class="bi bi-sliders"></i>Choose options</a>
    <?php endif; ?>
  </div>
</article>
