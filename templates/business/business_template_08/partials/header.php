<header class="cmc-header cmc-header-dark" data-cmc-header>
  <nav class="navbar navbar-expand-lg" aria-label="Main navigation">
    <div class="container">
      <?= $view->partial('brand', ['class' => 'navbar-brand']) ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#cmcNav" aria-controls="cmcNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="cmcNav">
        <?= $view->partial('nav-links', ['class' => 'ms-auto']) ?>
        <?php if ($view->site('phone') !== ''): ?><a class="cmc-header-phone d-none d-xl-inline-flex" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))) ?>"><i class="bi bi-telephone"></i><span><small>Call us</small><?= $view->e($view->site('phone')) ?></span></a><?php endif; ?>
        <?= $view->partial('header-cta') ?>
      </div>
    </div>
  </nav>
</header>
