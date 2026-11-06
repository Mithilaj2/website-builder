<?php /** @var string $content */ ?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="base-url" content="<?= e(rtrim(url(''), '/')) ?>">
  <title><?= e($title ?? 'Sign in') ?> · Channel Softech Website Builder</title>
  <link rel="icon" href="<?= e(asset('assets/images/logo-sm.png')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="auth-body d-flex align-items-center justify-content-center p-3">
  <?= $content ?>
  <div class="toast-container position-fixed top-0 end-0 p-3" id="toastArea"></div>
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"></h5></div><div class="modal-body"></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" data-confirm-ok>OK</button></div></div></div></div>
  <script src="<?= e(asset('assets/vendor/jquery/jquery.min.js')) ?>"></script>
  <script src="<?= e(asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
  <script src="<?= e(asset('assets/js/app.js')) ?>"></script>
</body>
</html>
