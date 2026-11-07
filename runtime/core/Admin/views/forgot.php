<div class="card shadow-sm border-0"><div class="card-body p-4">
  <h1 class="h5 mb-2">Reset your password</h1>
  <p class="small text-muted">Enter your admin email address. We will email you a link to choose a new password.</p>
  <form data-ajax data-reset="1" action="<?= e($admin->url('forgot')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="alert alert-danger form-alert d-none py-2 small"></div>
    <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" type="email" id="email" name="email" required autofocus></div>
    <button class="btn btn-primary w-100" type="submit" data-busy-text="Sending…">Send reset link</button>
  </form>
  <p class="small mt-3 mb-0"><a href="<?= e($admin->url('login')) ?>">Back to sign in</a></p>
</div></div>
