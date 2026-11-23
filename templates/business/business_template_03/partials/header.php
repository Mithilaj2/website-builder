<header class="vtx-header vtx-header-pill" data-vtx-header>
  <div class="container">
    <nav class="navbar navbar-expand-lg vtx-pillbar" aria-label="Main navigation">
      <?= $view->partial('brand', ['class' => 'navbar-brand']) ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vtxNav" aria-controls="vtxNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="vtxNav">
        <?= $view->partial('nav-links', ['class' => 'mx-auto']) ?>
        <?= $view->partial('header-cta') ?>
      </div>
    </nav>
  </div>
</header>
