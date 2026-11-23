<?php
/** @var \SiteRuntime\View $view @var array $quote @var array $notices @var string $message @var array $settings @var string $token */
$lines = $quote['lines'];
?>
<section class="wbshop wbshop-cart">
  <div class="wbshop-wrap">
    <h1 class="wbshop-title">Your cart</h1>
    <?php if ($message !== ''): ?><div class="wbshop-alert is-ok" role="status"><?= $view->e($message) ?></div><?php endif; ?>
    <?php foreach ($notices as $n): ?><div class="wbshop-alert is-warn" role="alert"><?= $view->e($n) ?></div><?php endforeach; ?>
    <?php if (!$lines): ?>
      <div class="wbshop-empty"><p>Your cart is empty.</p><a class="wbshop-btn" href="<?= $view->e($view->shopUrl()) ?>">Start shopping</a></div>
    <?php else: ?>
      <div class="wbshop-cols">
        <form class="wbshop-lines" method="post" action="<?= $view->e($view->shopUrl('cart')) ?>">
          <input type="hidden" name="_t" value="<?= $view->e($token) ?>">
          <?php foreach ($lines as $i => $l): $img = $view->img($l['image']); ?>
            <div class="wbshop-line">
              <a class="wbshop-line-img" href="<?= $view->e($view->productUrl($l)) ?>"><?php if ($img !== ''): ?><img src="<?= $img ?>" alt=""><?php endif; ?></a>
              <div class="wbshop-line-info">
                <a class="wbshop-line-name" href="<?= $view->e($view->productUrl($l)) ?>"><?= $view->e($l['name']) ?></a>
                <?php if ($l['variant_label'] !== ''): ?><span class="wbshop-muted"><?= $view->e(($l['option_name'] ?: 'Option') . ': ' . $l['variant_label']) ?></span><?php endif; ?>
                <span class="wbshop-muted"><?= $view->e($view->money($l['unit_price'])) ?> each</span>
              </div>
              <div class="wbshop-qty wbshop-qty-sm" data-wbshop-qty><button type="button" data-step="-1" aria-label="Fewer">−</button><input type="number" name="qty[<?= $i ?>]" value="<?= (int) $l['qty'] ?>" min="0" max="<?= (int) $l['max_qty'] ?>" aria-label="Quantity of <?= $view->e($l['name']) ?>" data-wbshop-autosave><button type="button" data-step="1" aria-label="More">+</button></div>
              <strong class="wbshop-line-total"><?= $view->e($view->money($l['gross'])) ?></strong>
              <button class="wbshop-remove" type="submit" name="line" value="<?= $i ?>" formaction="<?= $view->e($view->shopUrl('cart')) ?>" data-action="remove" aria-label="Remove <?= $view->e($l['name']) ?>">×</button>
            </div>
          <?php endforeach; ?>
          <input type="hidden" name="action" value="update" data-wbshop-action>
          <div class="wbshop-line-actions"><a href="<?= $view->e($view->shopUrl()) ?>" class="wbshop-link">← Continue shopping</a><button type="submit" class="wbshop-btn wbshop-btn-ghost">Update cart</button></div>
        </form>
        <aside class="wbshop-summary">
          <h2>Order summary</h2>
          <form class="wbshop-coupon" method="post" action="<?= $view->e($view->shopUrl('cart')) ?>">
            <input type="hidden" name="_t" value="<?= $view->e($token) ?>"><input type="hidden" name="action" value="coupon">
            <?php if ($quote['coupon']): ?>
              <div class="wbshop-coupon-on"><span><strong><?= $view->e($quote['coupon']['code']) ?></strong> applied<?= $quote['coupon']['description'] ? ' · ' . $view->e($quote['coupon']['description']) : '' ?></span><button type="submit" name="coupon" value="" class="wbshop-link">Remove</button></div>
            <?php else: ?>
              <label for="wbshop-coupon" class="visually-hidden">Coupon code</label>
              <input id="wbshop-coupon" name="coupon" placeholder="Coupon code" maxlength="30" autocapitalize="characters"><button type="submit" class="wbshop-btn wbshop-btn-ghost">Apply</button>
            <?php endif; ?>
          </form>
          <?= $view->capture(__DIR__ . '/totals.php', ['quote' => $quote, 'final' => false]) ?>
          <?php if ($quote['free_shipping_gap'] > 0): ?><p class="wbshop-hint">Add <?= $view->e($view->money($quote['free_shipping_gap'])) ?> more for free delivery.</p><?php endif; ?>
          <?php if (!$quote['min_order_ok']): ?><p class="wbshop-alert is-warn">The minimum order is <?= $view->e($view->money($settings['min_order'])) ?>.</p>
          <?php else: ?><a class="wbshop-btn wbshop-btn-block" href="<?= $view->e($view->shopUrl('checkout')) ?>">Proceed to checkout</a><?php endif; ?>
          <p class="wbshop-secure"><i class="bi bi-shield-lock" aria-hidden="true"></i> Secure checkout<?= !empty($settings['cod_enabled']) ? ' · Cash on delivery available' : '' ?></p>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>
