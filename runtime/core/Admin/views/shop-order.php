<?php
/** @var array $order @var array $events @var ?array $tx @var array $statuses @var string $customerUrl */
$o = $order;
$money = static fn ($p): string => '₹' . \SiteRuntime\Shop\ShopService::money((int) $p);
$badge = ['pending_payment' => 'secondary', 'placed' => 'primary', 'processing' => 'info', 'shipped' => 'warning', 'delivered' => 'success', 'cancelled' => 'danger'][$o['status']] ?? 'secondary';
$next = ['pending_payment' => ['pending_payment', 'cancelled'], 'placed' => ['placed', 'processing', 'shipped', 'delivered', 'cancelled'], 'processing' => ['processing', 'shipped', 'delivered', 'cancelled'],
    'shipped' => ['shipped', 'delivered', 'cancelled'], 'delivered' => ['delivered'], 'cancelled' => ['cancelled']][$o['status']];
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a class="btn btn-sm btn-light" href="<?= e($admin->url('orders')) ?>"><i class="bi bi-arrow-left"></i> Orders</a>
  <h2 class="h5 mb-0 ms-1 font-monospace"><?= e($o['order_no']) ?></h2>
  <span class="badge text-bg-<?= $badge ?>"><?= e($statuses[$o['status']]) ?></span>
  <span class="badge <?= $o['payment_status'] === 'paid' ? 'text-bg-success' : 'text-bg-light border' ?>"><?= $o['payment_method'] === 'cod' ? 'Cash on delivery' : 'Online' ?> · <?= e(ucfirst($o['payment_status'])) ?></span>
  <span class="text-muted small"><?= e(date('d M Y, h:i A', strtotime((string) $o['created_at']))) ?></span>
  <div class="ms-auto d-flex gap-2">
    <?php if ($o['invoice_no']): ?><a class="btn btn-sm btn-outline-primary" href="<?= e($admin->url('orders/' . $o['id'] . '/invoice')) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i>Invoice <?= e($o['invoice_no']) ?></a><?php endif; ?>
    <a class="btn btn-sm btn-light" href="<?= e($customerUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Customer’s page</a>
  </div>
</div>
<div class="row g-3">
  <div class="col-xl-8">
    <div class="card mb-3">
      <div class="card-header">Items</div>
      <div class="table-responsive"><table class="table mb-0 align-middle">
        <thead><tr><th>Item</th><th class="text-end">Price</th><th class="text-end">Qty</th><th class="text-end">GST</th><th class="text-end">Total</th></tr></thead>
        <tbody>
          <?php foreach ($o['items'] as $it): ?>
            <tr><td><div class="fw-semibold"><?= e($it['name']) ?></div><div class="small text-muted"><?= e(implode(' · ', array_filter([$it['variant_label'], $it['sku'] ? 'SKU ' . $it['sku'] : '', $it['hsn'] ? 'HSN ' . $it['hsn'] : '']))) ?></div></td>
              <td class="text-end"><?= e($money($it['unit_price'])) ?></td><td class="text-end"><?= (int) $it['qty'] ?></td>
              <td class="text-end small"><?= e(rtrim(rtrim((string) $it['gst_rate'], '0'), '.')) ?>%<div class="text-muted"><?= e($money($it['tax'])) ?></div></td><td class="text-end fw-semibold"><?= e($money((int) $it['unit_price'] * (int) $it['qty'])) ?><?php if ((int) $it['discount']): ?><div class="small text-success fw-normal">− <?= e($money($it['discount'])) ?></div><?php endif; ?></td></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="small">
          <tr><td colspan="4" class="text-end text-muted">Items</td><td class="text-end"><?= e($money($o['subtotal'])) ?></td></tr>
          <?php if ((int) $o['discount']): ?><tr><td colspan="4" class="text-end text-muted">Discount (<?= e($o['coupon_code']) ?>)</td><td class="text-end text-success">− <?= e($money($o['discount'])) ?></td></tr><?php endif; ?>
          <?php if (!(int) $o['prices_include_tax']): ?><tr><td colspan="4" class="text-end text-muted">GST</td><td class="text-end"><?= e($money($o['tax'])) ?></td></tr><?php endif; ?>
          <tr><td colspan="4" class="text-end text-muted">Shipping</td><td class="text-end"><?= e($money($o['shipping'])) ?></td></tr>
          <?php if ((int) $o['cod_fee']): ?><tr><td colspan="4" class="text-end text-muted">COD fee</td><td class="text-end"><?= e($money($o['cod_fee'])) ?></td></tr><?php endif; ?>
          <tr class="fs-6"><td colspan="4" class="text-end fw-bold">Total</td><td class="text-end fw-bold"><?= e($money($o['total'])) ?></td></tr>
          <?php if ((int) $o['prices_include_tax']): ?><tr><td colspan="4" class="text-end text-muted">Includes GST (<?= (int) $o['inter_state'] ? 'IGST' : 'CGST + SGST' ?>)</td><td class="text-end text-muted"><?= e($money($o['tax'])) ?></td></tr><?php endif; ?>
        </tfoot>
      </table></div>
    </div>
    <div class="card">
      <div class="card-header">History</div>
      <ul class="list-group list-group-flush small">
        <?php foreach (array_reverse($events) as $ev): ?>
          <li class="list-group-item d-flex gap-3"><span class="text-muted text-nowrap"><?= e(date('d M, h:i A', strtotime((string) $ev['created_at']))) ?></span><span><span class="badge text-bg-light border me-1"><?= e($ev['event']) ?></span><?= e((string) $ev['detail']) ?><?= $ev['actor'] ? ' <span class="text-muted">· ' . e($ev['actor']) . '</span>' : '' ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card mb-3">
      <div class="card-header">Update order</div>
      <div class="card-body">
        <form data-ajax action="<?= e($admin->url('api/orders/' . $o['id'])) ?>" id="orderForm">
          <div class="alert alert-danger form-alert d-none py-2 small"></div>
          <div class="mb-3"><label class="form-label">Status</label><select class="form-select" name="status"><?php foreach ($next as $k): ?><option value="<?= e($k) ?>"<?= $k === $o['status'] ? ' selected' : '' ?>><?= e($statuses[$k]) ?></option><?php endforeach; ?></select>
            <?php if ($o['status'] === 'pending_payment'): ?><div class="form-text">Waiting for the online payment. Cancel it if the customer will not pay.</div><?php endif; ?></div>
          <div class="row g-2 mb-3">
            <div class="col-6"><label class="form-label">Courier</label><input class="form-control" name="courier" maxlength="60" value="<?= e((string) $o['courier']) ?>" placeholder="e.g. India Post"></div>
            <div class="col-6"><label class="form-label">Tracking no.</label><input class="form-control" name="tracking_no" maxlength="80" value="<?= e((string) $o['tracking_no']) ?>"></div>
            <div class="col-12"><label class="form-label">Tracking link</label><input class="form-control" name="tracking_url" maxlength="255" value="<?= e((string) $o['tracking_url']) ?>" placeholder="https://"></div>
          </div>
          <?php if ($o['payment_method'] === 'cod' && $o['payment_status'] !== 'paid' && $o['status'] !== 'cancelled'): ?>
            <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="payment_status" value="paid" id="codPaid"><label class="form-check-label" for="codPaid">Cash received (mark as paid)</label></div>
          <?php endif; ?>
          <div class="mb-2"><label class="form-label">Private note</label><textarea class="form-control" name="admin_note" rows="2" maxlength="500"><?= e((string) $o['admin_note']) ?></textarea></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="notify" value="1" id="notifyCust" checked><label class="form-check-label" for="notifyCust">Email the customer when the order is shipped, delivered or cancelled</label></div>
          <button class="btn btn-primary w-100" type="submit">Save</button>
        </form>
        <?php if ($o['status'] !== 'pending_payment'): ?><button class="btn btn-link btn-sm px-0 mt-2" data-post="<?= e($admin->url('api/orders/' . $o['id'] . '/resend')) ?>"><i class="bi bi-envelope me-1"></i>Send the order confirmation again</button><?php endif; ?>
      </div>
    </div>
    <div class="card mb-3">
      <div class="card-header">Customer</div>
      <div class="card-body small">
        <div class="fw-semibold"><?= e($o['customer_name']) ?></div>
        <div><a href="mailto:<?= e($o['customer_email']) ?>"><?= e($o['customer_email']) ?></a> · <a href="tel:<?= e($o['customer_phone']) ?>"><?= e($o['customer_phone']) ?></a></div>
        <hr><div class="text-muted mb-1">Delivery address</div>
        <div><?= e($o['address']) ?><br><?= e($o['city']) ?> <?= e($o['pincode']) ?><br><?= e($o['state']) ?></div>
        <?php if ($o['customer_gstin']): ?><div class="mt-2">GSTIN: <span class="font-monospace"><?= e($o['customer_gstin']) ?></span></div><?php endif; ?>
        <?php if ($o['notes']): ?><hr><div class="text-muted mb-1">Customer’s note</div><div><?= nl2br(e($o['notes'])) ?></div><?php endif; ?>
      </div>
    </div>
    <?php if ($tx): ?>
      <div class="card">
        <div class="card-header">Online payment</div>
        <div class="card-body small">
          <div><span class="font-monospace"><?= e($tx['reference']) ?></span> · <?= e(ucfirst($tx['status'])) ?><?= $tx['mode'] === 'test' ? ' <span class="badge text-bg-warning">test</span>' : '' ?></div>
          <?php if ($tx['gateway_payment_id']): ?><div>Gateway ID <span class="font-monospace"><?= e($tx['gateway_payment_id']) ?></span></div><?php endif; ?>
          <a href="<?= e($admin->url('payments')) ?>?q=<?= e(rawurlencode($tx['reference'])) ?>">Open in Payments</a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
