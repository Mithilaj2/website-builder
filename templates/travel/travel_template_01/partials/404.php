<section class="wb-section hzt-404">
  <div class="container text-center">
    <span class="hzt-404-code">404</span>
    <h1 class="hzt-title">Looks like you’ve wandered off the map</h1>
    <p class="hzt-lead">The page may have moved or the package is no longer available.</p>
    <div class="hzt-actions justify-content-center mt-4">
      <a class="btn hzt-btn hzt-btn-primary" href="<?= $view->e($view->listingsEnabled() ? $view->listingsUrl() : $view->url('')) ?>"><?= $view->listingsEnabled() ? 'Explore packages' : 'Back to the homepage' ?><i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</section>
