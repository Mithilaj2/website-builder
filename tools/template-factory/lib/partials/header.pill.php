<header class="{{p}}-header {{p}}-header-pill" data-{{p}}-header>
  <div class="container">
    <nav class="navbar navbar-expand-lg {{p}}-pillbar" aria-label="Main navigation">
      <?= $view->partial('brand', ['class' => 'navbar-brand']) ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#{{p}}Nav" aria-controls="{{p}}Nav" aria-expanded="false" aria-label="Toggle navigation"><i class="bi bi-list"></i></button>
      <div class="collapse navbar-collapse" id="{{p}}Nav">
        <?= $view->partial('nav-links', ['class' => 'mx-auto']) ?>
        <?= $view->partial('header-cta') ?>
      </div>
    </nav>
  </div>
</header>
