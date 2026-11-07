<?php
/** @var \SiteRuntime\View $view @var array $order @var ?array $tx @var array $statuses @var string $invoiceUrl @var string $payUrl @var bool $canPay @var bool $payError @var string $token @var array $settings */
$o = $order;
$steps = ['placed' => 'Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
$keys = array_keys($steps);
$at = array_search($o['status'], $keys, true);
$failed = $o['status'] === 'pending_payment' && $tx && in_array($tx['status'], ['failed', 'cancelled'], true);
?>
<section class="wbshop wbshop-order">
  <div class="wbshop-wrap wbshop-narrow">
    <?php if ($o['status'] === 'cancelled'): ?>
      <div class="wbshop-hero is-muted"><i class="bi bi-x-circle" aria-hidden="true"></i><h1 class="wbshop-title">Order cancelled</h1><p>Order <?= $view->e($o['order_no']) ?> was cancelled. Contact us if you have any questions.</p></div>
    <?php elseif ($o['status'] === 'pending_payment'): ?>
      <div class="wbshop-hero is-warn"><i class="bi bi-hourglass-split" aria-hidden="true"></i>
        <h1 class="wbshop-title"><?= $failed ? 'Payment not completed' : 'Awaiting payment' ?></h1>
        <p>Order <?= $view->e($o['order_no']) ?> is saved but not paid yet<?= $failed && $tx['message'] ? ' (' . $view->e($tx['message']) . ')' : '' ?>.<?= $payError ? ' The payment could not be started; please try again.' : '' ?></p>
        <?php if (($_GET['pay'] ?? '') === 'stock'): ?><p>Some items are no longer in stock, so this order cannot be paid. Please place a new order.</p>
        <?php elseif ($canPay): ?>
          <form method="post" action="<?= $view->e($payUrl) ?>"><input type="hidden" name="_t" value="<?= $view->e($token) ?>"><button class="wbshop-btn" type="submit">Pay <?= $view->e($view->money($o['total'])) ?> now</button></form>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="wbshop-hero is-ok"><i class="bi bi-bag-check" aria-hidden="true"></i>
        <h1 class="wbshop-title">Thank you, <?= $view->e(strtok((string) $o['customer_name'], ' ')) ?>!</h1>
        <p>Your order <strong><?= $view->e($o['order_no']) ?></strong> is confirmed. A confirmation has been sent to <?= $view->e($o['customer_email']) ?>.</p>
      </div>
      <ol class="wbshop-steps">
        <?php foreach ($steps as $k => $label): $i = array_search($k, $keys, true); ?><li class="<?= $at !== false && $i <= $at ? 'is-done' : '' ?><?= $at === $i ? ' is-current' : '' ?>"><span></span><?= $view->e($label) ?></li><?php endforeach; ?>
      </ol>
      <?php if ($o['status'] === 'shipped' && $o['tracking_no']): ?>
        <div class="wbshop-alert is-ok">Shipped<?= $o['courier'] ? ' with ' . $view->e($o['courier']) : '' ?> · Tracking number <strong><?= $view->e($o['tracking_no']) ?></strong><?php if ($o['tracking_url']): ?> · <a href="<?= $view->e($o['tracking_url']) ?>" target="_blank" rel="noopener">Track your parcel</a><?php endif; ?></div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="wbshop-box">
      <div class="wbshop-order-head">
        <div><small>Order</small><strong><?= $view->e($o['order_no']) ?></strong></div>
        <div><small>Placed on</small><strong><?= $view->e(date('d M Y', strtotime((string) $o['created_at']))) ?></strong></div>
        <div><small>Payment</small><strong><?= $o['payment_method'] === 'cod' ? 'Cash on delivery' : 'Online' ?><?= $o['payment_status'] === 'paid' ? ' · Paid' : '' ?></strong></div>
        <div><small>Status</small><strong><?= $view->e($statuses[$o['status']] ?? $o['status']) ?></strong></div>
      </div>
      <ul class="wbshop-mini">
        <?php foreach ($o['items'] as $it): ?>
          <li><span><?= $view->e($it['name']) ?><?php if ($it['variant_label']): ?><small><?= $view->e($it['variant_label']) ?></small><?php endif; ?></span><span class="wbshop-muted">× <?= (int) $it['qty'] ?></span><strong><?= $view->e($view->money((int) $it['unit_price'] * (int) $it['qty'])) ?></strong></li>
        <?php endforeach; ?>
      </ul>
      <dl class="wbshop-totals">
        <div><dt>Items</dt><dd><?= $view->e($view->money($o['subtotal'])) ?></dd></div>
        <?php if ((int) $o['discount'] > 0): ?><div class="is-discount"><dt>Discount (<?= $view->e($o['coupon_code']) ?>)</dt><dd>− <?= $view->e($view->money($o['discount'])) ?></dd></div><?php endif; ?>
        <?php if (!(int) $o['prices_include_tax']): ?><div><dt>GST</dt><dd><?= $view->e($view->money($o['tax'])) ?></dd></div><?php endif; ?>
        <div><dt>Delivery</dt><dd><?= (int) $o['shipping'] > 0 ? $view->e($view->money($o['shipping'])) : 'Free' ?></dd></div>
        <?php if ((int) $o['cod_fee'] > 0): ?><div><dt>Cash on delivery fee</dt><dd><?= $view->e($view->money($o['cod_fee'])) ?></dd></div><?php endif; ?>
        <div class="is-total"><dt>Total</dt><dd><?= $view->e($view->money($o['total'])) ?></dd></div>
      </dl>
      <div class="wbshop-order-foot">
        <div><small>Delivering to</small><p><?= $view->e($o['customer_name']) ?><br><?= $view->e($o['address']) ?><br><?= $view->e($o['city'] . ' ' . $o['pincode'] . ', ' . $o['state']) ?><br><?= $view->e($o['customer_phone']) ?></p></div>
        <?php if ($invoiceUrl !== ''): ?><a class="wbshop-btn wbshop-btn-ghost" href="<?= $view->e($invoiceUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Download invoice</a><?php endif; ?>
      </div>
    </div>
    <p class="wbshop-center"><a class="wbshop-link" href="<?= $view->e($view->shopUrl()) ?>">Continue shopping</a></p>
  </div>
</section>
