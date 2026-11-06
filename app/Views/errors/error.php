<div class="card auth-card mx-auto shadow-sm" style="max-width:520px">
  <div class="card-body p-5 text-center">
    <div class="display-5 fw-bold text-primary mb-2"><?= e($code) ?></div>
    <h2 class="h5"><?= e($title) ?></h2>
    <p class="text-muted"><?= e($message ?: 'Something went wrong.') ?></p>
    <a href="<?= e(url('')) ?>" class="btn btn-primary">Back to dashboard</a>
  </div>
</div>
