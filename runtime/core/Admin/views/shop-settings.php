<?php /** @var array $settings @var array $states @var bool $paymentsReady @var string $paymentMode */
$s = $settings;
$r = static fn ($p): string => (int) $p ? rtrim(rtrim(number_format(((int) $p) / 100, 2, '.', ''), '0'), '.') : '0';
?>
<form data-ajax action="<?= e($admin->url('shop-settings')) ?>" id="shopSettingsForm" novalidate>
  <div class="alert alert-danger form-alert d-none py-2 small"></div>
  <div class="row g-3">
    <div class="col-xl-6">
      <div class="card mb-3"><div class="card-header">Shop</div><div class="card-body">
        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1" id="shopOn"<?= $s['enabled'] ? ' checked' : '' ?>><label class="form-check-label fw-semibold" for="shopOn">Shop is open (customers can browse and order)</label></div>
        <div class="small text-muted">Shop address: <a href="<?= e($shopUrl) ?>" target="_blank" rel="noopener"><?= e($shopUrl) ?></a> · link to it from buttons and menus with <code>page:shop</code></div>
      </div></div>
      <div class="card mb-3"><div class="card-header">Payment</div><div class="card-body">
        <?php if ($paymentsReady): ?><p class="small mb-3"><i class="bi bi-check-circle text-success me-1"></i>Online payment is set up<?= $paymentMode === 'test' ? ' (<strong>test mode</strong>: no real money)' : '' ?>. <a href="<?= e($admin->url('payments/settings')) ?>">Payment settings</a></p>
        <?php else: ?><p class="small mb-3"><i class="bi bi-exclamation-triangle text-warning me-1"></i>Online payment is not set up, so customers can only pay cash on delivery. <a href="<?= e($admin->url('payments/settings')) ?>">Set up online payments</a></p><?php endif; ?>
        <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="cod_enabled" value="1" id="codOn"<?= $s['cod_enabled'] ? ' checked' : '' ?>><label class="form-check-label" for="codOn">Offer cash on delivery</label></div>
        <div class="row g-2">
          <div class="col-6"><label class="form-label">COD fee (₹)</label><input class="form-control" name="cod_fee" inputmode="decimal" value="<?= e($r($s['cod_fee'])) ?>"></div>
          <div class="col-6"><label class="form-label">COD only up to (₹)</label><input class="form-control" name="cod_max" inputmode="decimal" value="<?= e($r($s['cod_max'])) ?>"></div>
        </div>
      </div></div>
      <div class="card mb-3"><div class="card-header">Delivery</div><div class="card-body"><div class="row g-2">
        <div class="col-6"><label class="form-label">Delivery charge (₹)</label><input class="form-control" name="shipping_flat" inputmode="decimal" value="<?= e($r($s['shipping_flat'])) ?>"></div>
        <div class="col-6"><label class="form-label">Free delivery above (₹)</label><input class="form-control" name="free_shipping_above" inputmode="decimal" value="<?= e($r($s['free_shipping_above'])) ?>"><div class="form-text">0 = never free.</div></div>
        <div class="col-6"><label class="form-label">Minimum order (₹)</label><input class="form-control" name="min_order" inputmode="decimal" value="<?= e($r($s['min_order'])) ?>"></div>
        <div class="col-6"><label class="form-label">Low-stock warning at</label><input class="form-control" name="low_stock" type="number" min="0" value="<?= (int) $s['low_stock'] ?>"></div>
        <div class="col-12"><label class="form-label">Delivery note (product page)</label><input class="form-control" name="shipping_note" maxlength="200" value="<?= e($s['shipping_note']) ?>"></div>
      </div></div></div>
    </div>
    <div class="col-xl-6">
      <div class="card mb-3"><div class="card-header">GST & invoices</div><div class="card-body"><div class="row g-2">
        <div class="col-12"><div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="prices_include_tax" value="1" id="incTax"<?= $s['prices_include_tax'] ? ' checked' : '' ?>><label class="form-check-label" for="incTax">Product prices include GST</label></div></div>
        <div class="col-12"><label class="form-label">Legal business name (on invoices)</label><input class="form-control" name="legal_name" maxlength="150" value="<?= e($s['legal_name']) ?>"></div>
        <div class="col-6"><label class="form-label">GSTIN</label><input class="form-control font-monospace text-uppercase" name="gstin" maxlength="15" value="<?= e($s['gstin']) ?>"><div class="form-text">Leave empty if not GST-registered (invoice says “Invoice”).</div></div>
        <div class="col-6"><label class="form-label">State you ship from *</label><select class="form-select" name="store_state"><?php foreach ($states as $st): ?><option<?= $st === $s['store_state'] ? ' selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?></select><div class="form-text">Same state: CGST + SGST; other states: IGST.</div></div>
        <div class="col-12"><label class="form-label">Invoice address</label><textarea class="form-control" name="invoice_address" rows="2" maxlength="400"><?= e($s['invoice_address']) ?></textarea></div>
        <div class="col-6"><label class="form-label">Order number prefix</label><input class="form-control text-uppercase" name="order_prefix" maxlength="6" value="<?= e($s['order_prefix']) ?>"></div>
        <div class="col-6"><label class="form-label">Invoice number prefix</label><input class="form-control text-uppercase" name="invoice_prefix" maxlength="12" value="<?= e($s['invoice_prefix']) ?>"><div class="form-text">e.g. INV- gives INV-26-27/0001</div></div>
      </div></div></div>
      <div class="card mb-3"><div class="card-header">Customer information</div><div class="card-body">
        <div class="mb-2"><label class="form-label">Returns policy (product page)</label><textarea class="form-control" name="return_policy" rows="2" maxlength="600"><?= e($s['return_policy']) ?></textarea></div>
        <div><label class="form-label">Note at checkout</label><textarea class="form-control" name="checkout_note" rows="2" maxlength="400"><?= e($s['checkout_note']) ?></textarea></div>
      </div></div>
      <button class="btn btn-primary" type="submit">Save shop settings</button>
    </div>
  </div>
</form>
