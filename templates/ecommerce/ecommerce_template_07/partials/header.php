<header class="ttt-header ttt-header-pill" data-ttt-header>
  <div class="container">
    <nav class="navbar navbar-expand-lg ttt-pillbar" aria-label="Main navigation">
      <?= $view->partial('brand', ['class' => 'navbar-brand']) ?>
      <?= $view->partial('shop-icons', ['class' => 'ms-auto ms-lg-3 order-lg-last']) ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#tttNav" aria-controls="tttNav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="tttNav">
        <?= $view->partial('nav-links', ['class' => 'mx-auto']) ?>
        <?= $view->partial('emergency', ['class' => 'is-compact']) ?>
        <?= $view->partial('header-cta') ?>
      </div>
    </nav>
  </div>
</header>
