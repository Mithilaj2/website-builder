<?php
/** @var \SiteRuntime\View $view @var array $quote @var array $values @var array $errors @var string $alert @var array $methods @var array $states @var array $settings @var string $token @var bool $testMode */
$val = static fn (string $k): string => (string) ($values[$k] ?? '');
$err = static fn (string $k): string => isset($errors[$k]) ? '<span class="wbshop-err">' . htmlspecialchars((string) $errors[$k], ENT_QUOTES, 'UTF-8') . '</span>' : '';
$inv = static fn (string $k): string => isset($errors[$k]) ? ' aria-invalid="true"' : '';
?>
<section class="wbshop wbshop-checkout">
  <div class="wbshop-wrap">
    <nav class="wbshop-crumbs" aria-label="Breadcrumb"><a href="<?= $view->e($view->shopUrl('cart')) ?>">Cart</a><span>/</span><span aria-current="page">Checkout</span></nav>
    <h1 class="wbshop-title">Checkout</h1>
    <?php if ($alert !== ''): ?><div class="wbshop-alert is-error" role="alert"><?= $view->e($alert) ?></div><?php endif; ?>
    <form class="wbshop-cols" method="post" action="<?= $view->e($view->shopUrl('checkout')) ?>" novalidate data-wbshop-checkout>
      <input type="hidden" name="_t" value="<?= $view->e($token) ?>">
      <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Leave empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <div class="wbshop-form">
        <fieldset class="wbshop-box">
          <legend>Contact</legend>
          <div class="wbshop-fields">
            <label class="is-full">Full name *<input name="name" value="<?= $view->e($val('name')) ?>" required maxlength="120" autocomplete="name"<?= $inv('name') ?>><?= $err('name') ?></label>
            <label>Email *<input type="email" name="email" value="<?= $view->e($val('email')) ?>" required maxlength="190" autocomplete="email"<?= $inv('email') ?>><?= $err('email') ?></label>
            <label>Mobile number *<input type="tel" name="phone" value="<?= $view->e($val('phone')) ?>" required maxlength="16" autocomplete="tel" inputmode="tel"<?= $inv('phone') ?>><?= $err('phone') ?></label>
          </div>
        </fieldset>
        <fieldset class="wbshop-box">
          <legend>Delivery address</legend>
          <div class="wbshop-fields">
            <label class="is-full">House no., street, area *<textarea name="address" rows="2" required maxlength="300" autocomplete="street-address"<?= $inv('address') ?>><?= $view->e($val('address')) ?></textarea><?= $err('address') ?></label>
            <label>Town / city *<input name="city" value="<?= $view->e($val('city')) ?>" required maxlength="80" autocomplete="address-level2"<?= $inv('city') ?>><?= $err('city') ?></label>
            <label>PIN code *<input name="pincode" value="<?= $view->e($val('pincode')) ?>" required maxlength="6" inputmode="numeric" pattern="[1-9][0-9]{5}" autocomplete="postal-code"<?= $inv('pincode') ?>><?= $err('pincode') ?></label>
            <label>State *<select name="state" required autocomplete="address-level1"<?= $inv('state') ?>><option value="">Select…</option><?php foreach ($states as $st): ?><option<?= $val('state') === $st ? ' selected' : '' ?>><?= $view->e($st) ?></option><?php endforeach; ?></select><?= $err('state') ?></label>
            <label>GSTIN (optional, for businesses)<input name="gstin" value="<?= $view->e($val('gstin')) ?>" maxlength="15" autocapitalize="characters"<?= $inv('gstin') ?>><?= $err('gstin') ?></label>
            <label class="is-full">Order notes (optional)<textarea name="notes" rows="2" maxlength="500"><?= $view->e($val('notes')) ?></textarea></label>
          </div>
        </fieldset>
        <fieldset class="wbshop-box">
          <legend>Payment</legend>
          <?php if (!$methods): ?><div class="wbshop-alert is-warn">Payments are not available at the moment. Please contact us to order.</div><?php endif; ?>
          <?php foreach ($methods as $k => $label): ?>
            <label class="wbshop-pay"><input type="radio" name="payment" value="<?= $view->e($k) ?>"<?= $val('payment') === $k ? ' checked' : '' ?> required><span><strong><?= $view->e($k === 'cod' ? 'Cash on delivery' : 'Pay online') ?></strong><small><?= $view->e($label) ?></small></span><i class="bi <?= $k === 'cod' ? 'bi-cash-coin' : 'bi-credit-card-2-front' ?>" aria-hidden="true"></i></label>
          <?php endforeach; ?>
          <?= $err('payment') ?>
          <?php if ($testMode): ?><p class="wbshop-hint">Online payments are in test mode: no real money is charged.</p><?php endif; ?>
        </fieldset>
        <?php if (($settings['checkout_note'] ?? '') !== ''): ?><p class="wbshop-muted"><?= $view->e($settings['checkout_note']) ?></p><?php endif; ?>
      </div>
      <aside class="wbshop-summary">
        <h2>Your order</h2>
        <ul class="wbshop-mini">
          <?php foreach ($quote['lines'] as $l): $img = $view->img($l['image']); ?>
            <li><span class="wbshop-mini-img"><?php if ($img !== ''): ?><img src="<?= $img ?>" alt=""><?php endif; ?><b><?= (int) $l['qty'] ?></b></span><span><?= $view->e($l['name']) ?><?php if ($l['variant_label'] !== ''): ?><small><?= $view->e($l['variant_label']) ?></small><?php endif; ?></span><strong><?= $view->e($view->money($l['gross'])) ?></strong></li>
          <?php endforeach; ?>
        </ul>
        <?php if (count($quotes ?? []) > 1): ?>
          <?php foreach ($quotes as $m => $q): ?><div data-wbshop-for="<?= $view->e($m) ?>"<?= $m === $val('payment') ? '' : ' hidden' ?>><?= $view->capture(__DIR__ . '/totals.php', ['quote' => $q, 'final' => true]) ?></div><?php endforeach; ?>
        <?php else: ?>
          <?= $view->capture(__DIR__ . '/totals.php', ['quote' => $quote, 'final' => true]) ?>
        <?php endif; ?>
        <?php if ($methods): ?><button type="submit" class="wbshop-btn wbshop-btn-block" data-busy="Placing your order…">Place order</button><?php endif; ?>
        <p class="wbshop-secure"><i class="bi bi-shield-lock" aria-hidden="true"></i> Your details are only used to deliver your order.</p>
      </aside>
    </form>
  </div>
</section>
