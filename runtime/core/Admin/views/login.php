<div class="card shadow-sm border-0"><div class="card-body p-4">
  <h1 class="h5 mb-3">Sign in</h1>
  <form data-ajax action="<?= e($admin->url('login')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="alert alert-danger form-alert d-none py-2 small"></div>
    <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" type="email" id="email" name="email" autocomplete="username" required autofocus></div>
    <div class="mb-2"><label class="form-label" for="password">Password</label><input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required></div>
    <div class="mb-3 text-end small"><a href="<?= e($admin->url('forgot')) ?>">Forgot your password?</a></div>
    <button class="btn btn-primary w-100" type="submit" data-busy-text="Signing in…">Sign in</button>
  </form>
</div></div>
