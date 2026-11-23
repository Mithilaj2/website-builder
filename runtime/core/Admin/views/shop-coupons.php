<?php $isOwner = ($user['role'] ?? '') === 'owner'; ?>
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
  <p class="text-muted small mb-0">Customers enter a code in the cart. Each code can be limited by date, minimum order and number of uses.</p>
  <?php if ($isOwner): ?><button class="btn btn-primary btn-sm ms-auto" id="couponAdd"><i class="bi bi-plus-lg me-1"></i>Add coupon</button><?php endif; ?>
</div>
<div class="card"><div id="couponTable" data-owner="<?= $isOwner ? '1' : '0' ?>"></div></div>
<div class="modal fade" id="couponModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
  <form data-ajax data-reload-table="coupons" id="couponForm" novalidate>
    <div class="modal-header"><h5 class="modal-title">Coupon</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <div class="row g-3">
        <div class="col-6"><label class="form-label">Code *</label><input class="form-control text-uppercase font-monospace" name="code" maxlength="30" required></div>
        <div class="col-6"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="paused">Paused</option></select></div>
        <div class="col-12"><label class="form-label">Description <span class="text-muted small">(shown to the customer)</span></label><input class="form-control" name="description" maxlength="160" placeholder="e.g. 10% off your first order"></div>
        <div class="col-6"><label class="form-label">Discount type</label><select class="form-select" name="type" id="couponType"><option value="percent">Percentage</option><option value="fixed">Fixed amount (₹)</option><option value="free_shipping">Free delivery</option></select></div>
        <div class="col-6" data-coupon-value><label class="form-label" id="couponValueLabel">Percent off</label><input class="form-control" name="value" inputmode="decimal"></div>
        <div class="col-6"><label class="form-label">Minimum order (₹)</label><input class="form-control" name="min_order" inputmode="decimal" placeholder="None"></div>
        <div class="col-6" data-coupon-max><label class="form-label">Maximum discount (₹)</label><input class="form-control" name="max_discount" inputmode="decimal" placeholder="No limit"></div>
        <div class="col-6"><label class="form-label">Valid from</label><input class="form-control" type="date" name="starts_at"></div>
        <div class="col-6"><label class="form-label">Valid until</label><input class="form-control" type="date" name="ends_at"></div>
        <div class="col-6"><label class="form-label">Usage limit</label><input class="form-control" name="usage_limit" inputmode="numeric" placeholder="Unlimited"></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>
