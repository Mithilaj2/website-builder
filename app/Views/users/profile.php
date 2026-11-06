<div class="row g-3">
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">Profile</div>
      <div class="card-body">
        <form data-ajax action="<?= e(url('profile')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="alert alert-danger form-alert d-none py-2 small"></div>
          <div class="mb-3"><label class="form-label">Name</label><input class="form-control" name="name" value="<?= e($user['name']) ?>" maxlength="120" required></div>
          <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" maxlength="190" required></div>
          <p class="small text-muted mb-3">Role: <strong><?= e(role_label($user['role'])) ?></strong> · Last login <?= e(format_date($user['last_login_at'], 'd M Y H:i')) ?> from <?= e($user['last_login_ip'] ?: '—') ?></p>
          <button class="btn btn-primary" type="submit">Save profile</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card">
      <div class="card-header">Change password</div>
      <div class="card-body">
        <form data-ajax data-reset="1" action="<?= e(url('profile/password')) ?>" novalidate autocomplete="off">
          <?= csrf_field() ?>
          <div class="alert alert-danger form-alert d-none py-2 small"></div>
          <div class="mb-3"><label class="form-label">Current password</label><input class="form-control" type="password" name="current_password" autocomplete="current-password" required></div>
          <div class="mb-3"><label class="form-label">New password</label><input class="form-control" type="password" name="password" autocomplete="new-password" required>
            <div class="form-text">At least 10 characters with letters and numbers.</div></div>
          <div class="mb-3"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required></div>
          <button class="btn btn-primary" type="submit">Change password</button>
        </form>
      </div>
    </div>
  </div>
</div>
