<section class="wb-section ldg-404">
  <div class="container text-center">
    <span class="ldg-404-code">404</span>
    <h1 class="ldg-title">We couldn’t find that page</h1>
    <p class="ldg-lead">The page may have moved or the service is no longer offered.</p>
    <div class="ldg-actions justify-content-center mt-4">
      <a class="btn ldg-btn ldg-btn-primary" href="<?= $view->e($view->listingsEnabled() ? $view->listingsUrl() : $view->url('')) ?>"><?= $view->listingsEnabled() ? 'See our services' : 'Back to the homepage' ?><i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</section>
