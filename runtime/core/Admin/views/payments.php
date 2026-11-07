<?php /** @var array $settings */ $isOwner = ($user['role'] ?? '') === 'owner'; ?>
<?php if (!$settings['enabled']): ?>
  <div class="alert alert-info d-flex gap-2 align-items-start"><i class="bi bi-credit-card fs-5"></i><div>Online payments are switched off.
    <?php if ($isOwner): ?><a href="<?= e($admin->url('payments/settings')) ?>">Set up online payments</a> to add a “Pay online” page to your website.<?php endif; ?></div></div>
<?php elseif (!$ready): ?>
  <div class="alert alert-warning">Online payments are on, but the <?= e($gatewayName) ?> keys for <?= e($settings['mode']) ?> mode are missing.<?php if ($isOwner): ?> <a href="<?= e($admin->url('payments/settings')) ?>">Enter them</a>.<?php endif; ?></div>
<?php else: ?>
  <div class="d-flex flex-wrap align-items-center gap-2 mb-3 small">
    <span class="badge text-bg-<?= $settings['mode'] === 'live' ? 'success' : 'warning' ?>"><?= $settings['mode'] === 'live' ? 'LIVE' : 'TEST MODE' ?></span>
    <span><?= e($gatewayName) ?> · Payment page: <a href="<?= e($payUrl) ?>" target="_blank" rel="noopener"><?= e($payUrl) ?></a></span>
    <span class="text-muted">· Link to it from any button or menu with <code>page:pay</code></span>
    <?php if ($isOwner): ?><a class="btn btn-sm btn-light ms-auto" href="<?= e($admin->url('payments/settings')) ?>"><i class="bi bi-gear me-1"></i>Settings</a><?php endif; ?>
  </div>
<?php endif; ?>
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <form class="filters d-flex flex-wrap gap-2" id="payFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Reference, name, email, phone, payment ID" aria-label="Search payments">
      <select name="status" class="form-select form-select-sm" aria-label="Status"><option value="">All attempts</option><option value="paid">Paid</option><option value="pending">Pending</option><option value="failed">Failed</option><option value="cancelled">Cancelled</option></select>
      <select name="mode" class="form-select form-select-sm" aria-label="Mode"><option value="">Live and test</option><option value="live">Live only</option><option value="test">Test only</option></select>
      <input type="date" name="from" class="form-control form-control-sm" aria-label="From date">
      <input type="date" name="to" class="form-control form-control-sm" aria-label="To date">
    </form>
    <span class="ms-auto small text-muted" id="payTotal"></span>
    <a class="btn btn-outline-primary btn-sm" id="payExport" href="<?= e($admin->url('api/payments/export')) ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
  </div>
  <div id="payTable"></div>
</div>
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body"></div>
  <div class="modal-footer">
    <a class="btn btn-light btn-sm me-auto" id="payReceipt" target="_blank" rel="noopener"><i class="bi bi-receipt me-1"></i>Customer receipt</a>
    <button class="btn btn-outline-primary btn-sm" id="payRecheck" data-busy-text="Asking the gateway…"><i class="bi bi-arrow-repeat me-1"></i>Re-check with gateway</button>
  </div>
</div></div></div>
