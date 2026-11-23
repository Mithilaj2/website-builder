<?php
$phone = (string) $view->site('phone');
$email = (string) $view->site('email');
$showBar = ($view->theme['header_style'] ?? '') !== 'minimal' && ($phone !== '' || $email !== '' || $view->site('emergency_phone') !== '' || $view->social());
?>
<?php if ($showBar): ?>
<div class="srh-topbar">
  <div class="container d-flex flex-wrap align-items-center gap-3">
    <?= $view->partial('emergency') ?>
    <?php if ($phone !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?>
    <?php if ($email !== ''): ?><a class="d-none d-sm-inline-flex" href="mailto:<?= $view->e($email) ?>"><i class="bi bi-envelope"></i><?= $view->e($email) ?></a><?php endif; ?>
    <?php if ($view->site('hours') !== ''): ?><span class="d-none d-lg-inline-flex"><i class="bi bi-clock"></i><?= $view->e($view->site('hours')) ?></span><?php endif; ?>
    <?= $view->partial('social', ['class' => 'ms-auto']) ?>
  </div>
</div>
<?php endif; ?>
<header class="srh-header srh-header-bar" data-srh-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <?= $view->partial('brand', ['class' => 'navbar-brand']) ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#srhNav" aria-controls="srhNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="srhNav">
        <?= $view->partial('nav-links', ['class' => 'ms-auto']) ?>
        <?= $view->partial('header-cta') ?>
      </div>
    </div>
  </nav>
</header>
