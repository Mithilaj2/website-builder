<div class="auth-card">
  <div class="text-center mb-4">
    <img src="<?= e(asset('assets/images/logo-sm.png')) ?>" alt="Channel Softech (P) Ltd." style="max-width:280px" class="img-fluid">
  </div>
  <div class="card shadow-sm border-0">
    <div class="card-body p-4">
      <h1 class="h5 mb-1">Website Builder</h1>
      <p class="text-muted small mb-4">Sign in with your Channel Softech account.</p>
      <form data-ajax action="<?= e(url('login')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="alert alert-danger form-alert d-none py-2 small" role="alert"></div>
        <div class="mb-3">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" autocomplete="username" required autofocus>
        </div>
        <div class="mb-4">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary w-100" type="submit" data-busy-text="Signing in…">Sign in</button>
      </form>
    </div>
  </div>
  <p class="text-center text-muted small mt-3">© <?= date('Y') ?> Channel Softech (P) Ltd.</p>
</div>
