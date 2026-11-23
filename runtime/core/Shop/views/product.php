<?php
/** @var \SiteRuntime\View $view @var array $product @var array $related @var bool $added */
$p = $product;
$images = array_values(array_filter(array_merge([(string) ($p['image'] ?? '')], $p['gallery'])));
$variants = $p['variants'];
$firstAvailable = null;
foreach ($variants as $v) {
    if (!(int) $p['track_stock'] || (int) $v['stock'] > 0) {
        $firstAvailable = (int) $v['id'];
        break;
    }
}
$needOption = ($_GET['need_option'] ?? '') === '1';
?>
<section class="wbshop wbshop-product">
  <div class="wbshop-wrap">
    <nav class="wbshop-crumbs" aria-label="Breadcrumb"><a href="<?= $view->e($view->url('')) ?>">Home</a><span>/</span><a href="<?= $view->e($view->shopUrl()) ?>">Shop</a><?php if ($p['category_slug']): ?><span>/</span><a href="<?= $view->e($view->shopUrl('', ['c' => $p['category_slug']])) ?>"><?= $view->e($p['category_name']) ?></a><?php endif; ?></nav>
    <div class="wbshop-pgrid">
      <div class="wbshop-gallery" data-wbshop-gallery>
        <div class="wbshop-gallery-main">
          <?php if ($images): ?><img src="<?= $view->img($images[0]) ?>" alt="<?= $view->e($p['name']) ?>" data-wbshop-main><?php endif; ?>
          <?php if ($p['discount_pct'] > 0 && $p['in_stock']): ?><span class="wbshop-flag">−<?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
          <div class="wbshop-thumbs">
            <?php foreach ($images as $i => $ref): ?><button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-wbshop-thumb="<?= $view->img($ref) ?>" aria-label="Show image <?= $i + 1 ?>"><img src="<?= $view->img($ref) ?>" alt="" loading="lazy"></button><?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="wbshop-info" id="buy">
        <?php if ($p['category_name']): ?><span class="wbshop-card-cat"><?= $view->e($p['category_name']) ?></span><?php endif; ?>
        <h1 class="wbshop-title"><?= $view->e($p['name']) ?></h1>
        <div class="wbshop-price wbshop-price-lg">
          <strong data-wbshop-price><?= $view->e($view->money($p['display_price'])) ?></strong>
          <?php if ((int) ($p['compare_price'] ?? 0) > $p['display_price']): ?><s><?= $view->e($view->money($p['compare_price'])) ?></s><span class="wbshop-save">Save <?= (int) $p['discount_pct'] ?>%</span><?php endif; ?>
        </div>
        <p class="wbshop-muted wbshop-taxnote"><?= !empty($view->setting('shop', 'prices_include_tax', true)) ? 'Inclusive of all taxes.' : 'GST extra.' ?> <?= $view->e((string) $view->setting('shop', 'shipping_note', '')) ?></p>
        <?php if (($p['short_description'] ?? '') !== ''): ?><p class="wbshop-lead"><?= $view->text($p['short_description']) ?></p><?php endif; ?>
        <?php if ($added): ?><div class="wbshop-alert is-ok" role="status">Added to your cart. <a href="<?= $view->e($view->shopUrl('cart')) ?>">View cart</a></div><?php endif; ?>
        <?php if ($needOption): ?><div class="wbshop-alert is-error" role="alert">Please choose <?= $view->e(strtolower((string) ($p['option_name'] ?: 'an option'))) ?>.</div><?php endif; ?>
        <?php if ($p['in_stock']): ?>
          <form class="wbshop-buy" method="post" action="<?= $view->e($view->shopUrl('cart')) ?>" data-wbshop-buy>
            <input type="hidden" name="_t" value="<?= $view->e($view->shopToken()) ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
            <?php if ($variants): ?>
              <fieldset class="wbshop-options">
                <legend><?= $view->e($p['option_name'] ?: 'Option') ?></legend>
                <?php foreach ($variants as $v): $out = (int) $p['track_stock'] && (int) $v['stock'] <= 0; $price = (int) ($v['price'] ?? $p['price']); ?>
                  <label class="wbshop-opt<?= $out ? ' is-out' : '' ?>"><input type="radio" name="variant_id" value="<?= (int) $v['id'] ?>" data-price="<?= $view->e($view->money($price)) ?>"<?= $out ? ' disabled' : '' ?><?= !$needOption && (int) $v['id'] === $firstAvailable ? ' checked' : '' ?> required><span><?= $view->e($v['label']) ?></span></label>
                <?php endforeach; ?>
              </fieldset>
            <?php endif; ?>
            <div class="wbshop-buy-row">
              <div class="wbshop-qty" data-wbshop-qty><button type="button" data-step="-1" aria-label="Fewer">−</button><input type="number" name="qty" value="1" min="1" max="20" inputmode="numeric" aria-label="Quantity"><button type="button" data-step="1" aria-label="More">+</button></div>
              <button type="submit" class="wbshop-btn" name="then" value="product"><i class="bi bi-bag-plus" aria-hidden="true"></i> Add to cart</button>
              <button type="submit" class="wbshop-btn wbshop-btn-dark" name="then" value="checkout">Buy now</button>
            </div>
          </form>
          <?php if ((int) $p['track_stock'] && !$variants && (int) $p['stock'] <= (int) $view->setting('shop', 'low_stock', 5)): ?><p class="wbshop-stock is-low">Only <?= (int) $p['stock'] ?> left in stock</p><?php else: ?><p class="wbshop-stock">In stock</p><?php endif; ?>
        <?php else: ?>
          <p class="wbshop-stock is-out">Currently sold out</p>
        <?php endif; ?>
        <ul class="wbshop-facts">
          <?php if ($p['sku']): ?><li><span>SKU</span><?= $view->e($p['sku']) ?></li><?php endif; ?>
          <?php if ($view->setting('shop', 'cod_enabled', false)): ?><li><span>Payment</span>Pay online or cash on delivery</li><?php endif; ?>
          <?php if ((string) $view->setting('shop', 'return_policy', '') !== ''): ?><li><span>Returns</span><?= $view->e($view->setting('shop', 'return_policy', '')) ?></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <?php if (trim(strip_tags((string) ($p['description'] ?? ''))) !== ''): ?>
      <div class="wbshop-desc"><h2>Product details</h2><div class="wbshop-prose"><?= $view->rich($p['description']) ?></div></div>
    <?php endif; ?>
    <?php if ($related): ?>
      <div class="wbshop-related"><h2>You may also like</h2><div class="wbshop-grid">
        <?php foreach ($related as $product): ?><?= $view->capture(__DIR__ . '/card.php', ['product' => $product]) ?><?php endforeach; ?>
      </div></div>
    <?php endif; ?>
  </div>
</section>
