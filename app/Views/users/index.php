<div class="row g-3">
  <div class="col-xl-9">
    <div class="card">
      <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <form class="filters d-flex flex-wrap gap-2" id="userFilters" role="search">
          <input type="search" name="q" class="form-control form-control-sm" placeholder="Search name or email" aria-label="Search users">
          <select name="role" class="form-select form-select-sm" aria-label="Role"><option value="">All roles</option>
            <?php foreach ($roles as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?></select>
          <select name="status" class="form-select form-select-sm" aria-label="Status"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select>
        </form>
        <button class="btn btn-primary btn-sm ms-auto" id="btnAddUser"><i class="bi bi-plus-lg me-1"></i>Add user</button>
      </div>
      <div id="userTable"></div>
    </div>
  </div>
  <div class="col-xl-3">
    <div class="card">
      <div class="card-header">What each role can do</div>
      <ul class="list-group list-group-flush small">
        <li class="list-group-item"><strong>Super Admin</strong><br>Everything, including users and roles.</li>
        <li class="list-group-item"><strong>Admin</strong><br>Categories, templates, clients, websites and activity log. No user management.</li>
        <li class="list-group-item"><strong>Designer</strong><br>Manage categories and templates. Read-only for clients and websites.</li>
        <li class="list-group-item"><strong>Viewer</strong><br>Read-only access to the dashboard, catalogue, clients and websites.</li>
      </ul>
    </div>
  </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form data-ajax data-reload-table="users" novalidate autocomplete="off">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">User</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <div class="row g-3">
          <div class="col-12"><label class="form-label">Name *</label><input class="form-control" name="name" maxlength="120" required></div>
          <div class="col-12"><label class="form-label">Email *</label><input class="form-control" type="email" name="email" maxlength="190" required></div>
          <div class="col-md-6"><label class="form-label">Role</label><select class="form-select" name="role">
            <?php foreach ($roles as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
          <div class="col-md-6"><label class="form-label">Password <span class="pw-required">*</span></label><input class="form-control" type="password" name="password" autocomplete="new-password"></div>
          <div class="col-md-6"><label class="form-label">Confirm password</label><input class="form-control" type="password" name="password_confirmation" autocomplete="new-password"></div>
          <div class="col-12 form-text pw-hint">At least 10 characters with letters and numbers.</div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save user</button></div>
    </form>
  </div></div>
</div>
