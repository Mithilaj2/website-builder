<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e($csrf) ?>">
  <meta name="base-url" content="<?= e($admin->url()) ?>">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> · <?= e($siteName) ?></title>
  <link rel="stylesheet" href="<?= e($base) ?>/assets/lib/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= e($base) ?>/assets/lib/bootstrap-icons/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= e($admin->asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e($admin->asset('css/admin.css')) ?>">
</head>
<body class="auth-body site-admin d-flex align-items-center justify-content-center p-3">
  <div class="auth-card">
    <div class="text-center mb-4"><span class="site-brand-mark site-brand-lg"><?= e(mb_strtoupper(mb_substr($siteName, 0, 1))) ?></span><div class="h5 mt-2 mb-0"><?= e($siteName) ?></div><small class="text-muted">Website admin</small></div>
    <?= $content ?>
    <p class="text-center small mt-3"><a href="<?= e($base) ?>/">← Back to the website</a></p>
  </div>
  <div class="toast-container position-fixed top-0 end-0 p-3" id="toastArea"></div>
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"></h5></div><div class="modal-body"></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" data-confirm-ok>OK</button></div></div></div></div>
  <script src="<?= e($admin->asset('vendor/jquery/jquery.min.js')) ?>"></script>
  <script src="<?= e($base) ?>/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="<?= e($admin->asset('js/app.js')) ?>"></script>
</body>
</html>
