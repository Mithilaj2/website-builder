<?php
/** @var string $content */
$user = auth_user();
$title = $title ?? 'Dashboard';
$active = $active ?? '';
$scripts = $scripts ?? [];
$perms = array_filter([
    'categories.manage', 'templates.manage', 'clients.manage', 'users.manage', 'websites.manage',
], 'can');
$nav = [
    ['heading' => 'Overview'],
    ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'speedometer2', 'url' => '', 'perm' => 'dashboard.view'],
    ['heading' => 'Catalogue'],
    ['key' => 'categories', 'label' => 'Categories', 'icon' => 'grid-3x3-gap', 'url' => 'categories', 'perm' => 'categories.view'],
    ['key' => 'templates', 'label' => 'Templates', 'icon' => 'layout-text-window-reverse', 'url' => 'templates', 'perm' => 'templates.view'],
    ['heading' => 'Clients'],
    ['key' => 'clients', 'label' => 'Clients', 'icon' => 'people', 'url' => 'clients', 'perm' => 'clients.view'],
    ['heading' => 'Administration'],
    ['key' => 'users', 'label' => 'Users & Roles', 'icon' => 'person-gear', 'url' => 'users', 'perm' => 'users.manage'],
    ['key' => 'activity', 'label' => 'Activity Log', 'icon' => 'clock-history', 'url' => 'activity', 'perm' => 'activity.view'],
];
// Drop headings with no visible items beneath them.
$visible = [];
foreach ($nav as $i => $item) {
    if (isset($item['heading'])) {
        $hasChild = false;
        for ($j = $i + 1; $j < count($nav) && !isset($nav[$j]['heading']); $j++) {
            if (can($nav[$j]['perm'])) { $hasChild = true; break; }
        }
        if ($hasChild) { $visible[] = $item; }
    } elseif (can($item['perm'])) {
        $visible[] = $item;
    }
}
$flash = \App\Core\Session::flash('success');
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <meta name="base-url" content="<?= e(rtrim(url(''), '/')) ?>">
  <meta name="permissions" content="<?= e(implode(',', $perms)) ?>">
  <title><?= e($title) ?> · Channel Softech Website Builder</title>
  <link rel="icon" href="<?= e(asset('assets/images/logo-sm.png')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/vendor/bootstrap/css/bootstrap.min.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<aside class="wb-sidebar" aria-label="Main navigation">
  <div class="brand">
    <a href="<?= e(url('')) ?>"><img src="<?= e(asset('assets/images/logo-sm.png')) ?>" alt="Channel Softech (P) Ltd."></a>
    <small>Website Builder</small>
  </div>
  <nav class="wb-nav">
    <?php foreach ($visible as $item): ?>
      <?php if (isset($item['heading'])): ?>
        <div class="nav-heading"><?= e($item['heading']) ?></div>
      <?php else: ?>
        <a href="<?= e(url($item['url'])) ?>" class="<?= $active === $item['key'] ? 'active' : '' ?>"<?= $active === $item['key'] ? ' aria-current="page"' : '' ?>>
          <i class="bi bi-<?= e($item['icon']) ?>"></i><span><?= e($item['label']) ?></span>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</aside>
<div class="wb-backdrop"></div>

<div class="wb-main">
  <header class="wb-topbar">
    <button class="btn btn-light d-lg-none" type="button" data-toggle-sidebar aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
    <h1 class="page-title"><?= e($title) ?></h1>
    <div class="ms-auto dropdown">
      <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-person-circle fs-5"></i>
        <span class="d-none d-sm-inline text-start lh-sm"><span class="d-block fw-semibold"><?= e($user['name']) ?></span><small class="text-muted"><?= e(role_label($user['role'])) ?></small></span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?= e(url('profile')) ?>"><i class="bi bi-person me-2"></i>My profile</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="#" data-logout><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
      </ul>
    </div>
  </header>

  <main class="wb-content">
    <?= $content ?>
  </main>
  <footer class="wb-footer">© <?= date('Y') ?> Channel Softech (P) Ltd. · Imagine. Implement</footer>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastArea"></div>
<?php if ($flash): ?><span id="flashData" data-message="<?= e($flash) ?>" hidden></span><?php endif; ?>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Please confirm</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body"></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" data-confirm-ok>Confirm</button></div>
  </div></div>
</div>

<script src="<?= e(asset('assets/vendor/jquery/jquery.min.js')) ?>"></script>
<script src="<?= e(asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('assets/js/app.js')) ?>"></script>
<?php foreach ($scripts as $s): ?>
<script src="<?= e(asset($s)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
