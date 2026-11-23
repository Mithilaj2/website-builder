<?php
/** Price breakdown. @var \SiteRuntime\View $view @var array $quote @var bool $final */
?>
<dl class="wbshop-totals">
  <div><dt>Items (<?= (int) $quote['count'] ?>)</dt><dd><?= $view->e($view->money($quote['subtotal'])) ?></dd></div>
  <?php if ($quote['discount'] > 0): ?><div class="is-discount"><dt>Discount<?= $quote['coupon'] ? ' (' . $view->e($quote['coupon']['code']) . ')' : '' ?></dt><dd>− <?= $view->e($view->money($quote['discount'])) ?></dd></div><?php endif; ?>
  <?php if (!$quote['prices_include_tax']): ?><div><dt>GST</dt><dd><?= $view->e($view->money($quote['tax'])) ?></dd></div><?php endif; ?>
  <div><dt>Delivery</dt><dd><?= $quote['shipping'] > 0 ? $view->e($view->money($quote['shipping'])) : ($quote['coupon'] && $quote['coupon']['type'] === 'free_shipping' ? 'Free (coupon)' : 'Free') ?></dd></div>
  <?php if ($quote['cod_fee'] > 0): ?><div><dt>Cash on delivery fee</dt><dd><?= $view->e($view->money($quote['cod_fee'])) ?></dd></div><?php endif; ?>
  <div class="is-total"><dt><?= $final ? 'Total to pay' : 'Total' ?></dt><dd><?= $view->e($view->money($quote['total'])) ?></dd></div>
  <?php if ($quote['prices_include_tax'] && $quote['tax'] > 0): ?><div class="is-note"><dt>Includes GST</dt><dd><?= $view->e($view->money($quote['tax'])) ?></dd></div><?php endif; ?>
</dl>
