<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <form class="filters d-flex flex-wrap gap-2" id="clientFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search name, contact, email, phone" aria-label="Search clients">
      <select name="status" class="form-select form-select-sm" aria-label="Status">
        <option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
      </select>
    </form>
    <?php if (can('clients.manage')): ?>
      <button class="btn btn-primary btn-sm ms-auto" id="btnAddClient"><i class="bi bi-plus-lg me-1"></i>Add client</button>
    <?php endif; ?>
  </div>
  <div id="clientTable"></div>
</div>
<?php if (can('clients.manage')) { include __DIR__ . '/_form.php'; } ?>
