<div class="card shadow-sm border-0"><div class="card-body p-4">
  <h1 class="h5 mb-3">Choose a new password</h1>
  <?php if (!$valid): ?>
    <div class="alert alert-warning small">This reset link is invalid or has expired.</div>
    <a class="btn btn-primary w-100" href="<?= e($admin->url('forgot')) ?>">Request a new link</a>
  <?php else: ?>
  <form data-ajax action="<?= e($admin->url('reset')) ?>" novalidate autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="email" value="<?= e($email) ?>"><input type="hidden" name="token" value="<?= e($token) ?>">
    <div class="alert alert-danger form-alert d-none py-2 small"></div>
    <div class="mb-3"><label class="form-label">New password</label><input class="form-control" type="password" name="password" autocomplete="new-password" required><div class="form-text">At least 10 characters with letters and numbers.</div></div>
    <div class="mb-3"><label class="form-label">Confirm new password</label><input class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required></div>
    <button class="btn btn-primary w-100" type="submit">Save new password</button>
  </form>
  <?php endif; ?>
</div></div>
