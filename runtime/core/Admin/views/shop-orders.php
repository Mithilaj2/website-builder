<?php /** @var array $summary @var array $statuses */ $isOwner = ($user['role'] ?? '') === 'owner'; ?>
<?php if (!$shopEnabled): ?>
  <div class="alert alert-warning d-flex gap-2 align-items-start"><i class="bi bi-shop fs-5"></i><div>The shop is switched off, so customers cannot place orders.<?php if ($isOwner): ?> <a href="<?= e($admin->url('shop-settings')) ?>">Switch it on in Shop settings</a>.<?php endif; ?></div></div>
<?php endif; ?>
<div class="row g-3 mb-3">
  <div class="col-sm-4"><div class="card stat-card h-100"><div class="card-body"><div class="small text-muted">Orders to process</div><div class="fs-3 fw-bold"><?= (int) $summary['open'] ?></div></div></div></div>
  <div class="col-sm-4"><div class="card stat-card h-100"><div class="card-body"><div class="small text-muted">Orders this month</div><div class="fs-3 fw-bold"><?= (int) $summary['month_count'] ?></div></div></div></div>
  <div class="col-sm-4"><div class="card stat-card h-100"><div class="card-body"><div class="small text-muted">Sales this month</div><div class="fs-3 fw-bold">₹<?= e(\SiteRuntime\Shop\ShopService::money((int) $summary['month_total'])) ?></div></div></div></div>
</div>
<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <form class="filters d-flex flex-wrap gap-2" id="orderFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Order no., name, email, phone, invoice" aria-label="Search orders">
      <select name="status" class="form-select form-select-sm" aria-label="Status"><option value="">All orders</option><option value="open">To process</option><?php foreach ($statuses as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
      <select name="payment" class="form-select form-select-sm" aria-label="Payment"><option value="">Any payment</option><option value="online">Online</option><option value="cod">Cash on delivery</option></select>
      <input type="date" name="from" class="form-control form-control-sm" aria-label="From date">
      <input type="date" name="to" class="form-control form-control-sm" aria-label="To date">
    </form>
    <span class="ms-auto small text-muted" id="orderTotal"></span>
    <a class="btn btn-outline-primary btn-sm" id="orderExport" href="<?= e($admin->url('api/orders/export')) ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
  </div>
  <div id="orderTable"></div>
</div>
