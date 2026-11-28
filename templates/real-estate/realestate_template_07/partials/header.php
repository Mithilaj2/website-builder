<?php
$phone = (string) $view->site('phone');
$email = (string) $view->site('email');
?>
<header class="bwr-header bwr-header-center" data-bwr-header>
  <div class="bwr-masthead d-none d-lg-block">
    <div class="container">
      <div class="bwr-masthead-row">
        <div class="bwr-masthead-side">
          <?php if ($phone !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><i class="bi bi-telephone"></i><?= $view->e($phone) ?></a><?php endif; ?>
          <?php if ($email !== ''): ?><a href="mailto:<?= $view->e($email) ?>"><i class="bi bi-envelope"></i><?= $view->e($email) ?></a><?php endif; ?>
        </div>
        <?= $view->partial('brand', ['class' => 'bwr-brand-center']) ?>
        <div class="bwr-masthead-side justify-content-end"><?= $view->partial('emergency') ?><?= $view->partial('shop-icons') ?><?= $view->partial('header-cta') ?></div>
      </div>
    </div>
  </div>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <?= $view->partial('brand', ['class' => 'navbar-brand d-lg-none']) ?>
      <?= $view->partial('shop-icons', ['class' => 'ms-auto d-lg-none']) ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#bwrNav" aria-controls="bwrNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="bwrNav">
        <?= $view->partial('nav-links', ['class' => 'mx-auto']) ?>
        <div class="d-lg-none mt-3"><?= $view->partial('header-cta') ?></div>
      </div>
    </div>
  </nav>
</header>
