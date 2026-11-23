<header class="scd-header scd-header-pill" data-scd-header>
  <div class="container">
    <nav class="navbar navbar-expand-lg scd-pillbar" aria-label="Main navigation">
      <?= $view->partial('brand', ['class' => 'navbar-brand']) ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#scdNav" aria-controls="scdNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="scdNav">
        <?= $view->partial('nav-links', ['class' => 'mx-auto']) ?>
        <?= $view->partial('emergency', ['class' => 'is-compact']) ?>
        <?= $view->partial('header-cta') ?>
      </div>
    </nav>
  </div>
</header>
