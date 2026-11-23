<section class="wb-section skl-404">
  <div class="container">
    <span class="skl-404-code">404</span>
    <h1 class="skl-title">Nothing to see here. Yet.</h1>
    <p class="skl-lead">The page you were looking for has moved or never existed.</p>
    <div class="skl-actions mt-4"><a class="btn skl-btn skl-btn-dark" href="<?= $view->e($view->listingsEnabled() ? $view->listingsUrl() : $view->url('')) ?>"><?= $view->listingsEnabled() ? 'See our work' : 'Back to the homepage' ?><i class="bi bi-arrow-right"></i></a></div>
  </div>
</section>
