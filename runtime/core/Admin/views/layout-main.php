<?php
/** @var \SiteRuntime\Admin\App $admin */
$isOwner = ($user['role'] ?? '') === 'owner';
$nav = [
    ['dashboard', '', 'speedometer2', 'Dashboard'],
    ['content', 'content', 'layout-text-sidebar', 'Pages & sections'],
    ['media', 'media', 'images', 'Media library'],
    ['navigation', 'navigation', 'signpost-split', 'Navigation'],
    ['appearance', 'appearance', 'palette', 'Appearance'],
    ['enquiries', 'enquiries', 'envelope', 'Enquiries'],
];
if ($isOwner || !empty($paymentsOn)) {
    $nav[] = ['payments', 'payments', 'credit-card', 'Payments'];
}
if ($isOwner) {
    $nav[] = ['settings', 'settings', 'sliders', 'Settings'];
}
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e($csrf) ?>">
  <meta name="base-url" content="<?= e($admin->url()) ?>">
  <meta name="permissions" content="websites.manage">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> · <?= e($siteName) ?> admin</title>
  <link rel="stylesheet" href="<?= e($base) ?>/assets/lib/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= e($base) ?>/assets/lib/bootstrap-icons/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= e($admin->asset('css/app.css')) ?>">
  <link rel="stylesheet" href="<?= e($admin->asset('css/admin.css')) ?>">
</head>
<body class="site-admin">
<aside class="wb-sidebar" aria-label="Admin navigation">
  <div class="brand">
    <a href="<?= e($admin->url()) ?>" class="site-brand"><span class="site-brand-mark"><?= e(mb_strtoupper(mb_substr($siteName, 0, 1))) ?></span><span class="site-brand-name"><?= e($siteName) ?></span></a>
    <small>Website admin</small>
  </div>
  <nav class="wb-nav">
    <?php foreach ($nav as [$key, $path, $icon, $label]): ?>
      <a href="<?= e($admin->url($path)) ?>" class="<?= $active === $key ? 'active' : '' ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>><i class="bi bi-<?= $icon ?>"></i><span><?= e($label) ?></span>
        <?php if ($key === 'enquiries' && $newEnquiries > 0): ?><span class="badge text-bg-danger ms-auto"><?= (int) $newEnquiries ?></span><?php endif; ?></a>
    <?php endforeach; ?>
    <div class="nav-heading">Website</div>
    <a href="<?= e($base) ?>/" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i><span>View website</span></a>
  </nav>
</aside>
<div class="wb-backdrop"></div>
<div class="wb-main">
  <header class="wb-topbar">
    <button class="btn btn-light d-lg-none" type="button" data-toggle-sidebar aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
    <h1 class="page-title"><?= e($title) ?></h1>
    <div class="ms-auto dropdown">
      <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-circle fs-5"></i><span class="d-none d-sm-inline text-start lh-sm"><span class="d-block fw-semibold"><?= e($user['name']) ?></span><small class="text-muted"><?= $isOwner ? 'Owner' : 'Editor' ?></small></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?= e($admin->url('profile')) ?>"><i class="bi bi-person me-2"></i>My profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="#" data-admin-logout><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
      </ul>
    </div>
  </header>
  <main class="wb-content"><?= $content ?></main>
  <footer class="wb-footer">Website by Channel Softech (P) Ltd.</footer>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastArea"></div>
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Please confirm</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" data-confirm-ok>Confirm</button></div>
  </div></div>
</div>
<script src="<?= e($admin->asset('vendor/jquery/jquery.min.js')) ?>"></script>
<script src="<?= e($base) ?>/assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?= e($admin->asset('js/app.js')) ?>"></script>
<script src="<?= e($admin->asset('js/admin.js')) ?>"></script>
<?php foreach ($scripts as $s): ?><script src="<?= e($admin->asset($s)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
