<?php
$phone = (string) $view->site('phone');
$email = (string) $view->site('email');
?>
<header class="gfh-header gfh-header-center" data-gfh-header>
  <div class="gfh-masthead d-none d-lg-block">
    <div class="container">
      <div class="gfh-masthead-row">
        <div class="gfh-masthead-side">
          <?php if ($phone !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?>
          <?php if ($email !== ''): ?><a href="mailto:<?= $view->e($email) ?>"><i class="bi bi-envelope"></i><?= $view->e($email) ?></a><?php endif; ?>
        </div>
        <?= $view->partial('brand', ['class' => 'gfh-brand-center']) ?>
        <div class="gfh-masthead-side justify-content-end"><?= $view->partial('header-cta') ?></div>
      </div>
    </div>
  </div>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <?= $view->partial('brand', ['class' => 'navbar-brand d-lg-none']) ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#gfhNav" aria-controls="gfhNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="gfhNav">
        <?= $view->partial('nav-links', ['class' => 'mx-auto']) ?>
        <div class="d-lg-none mt-3"><?= $view->partial('header-cta') ?></div>
      </div>
    </div>
  </nav>
</header>
