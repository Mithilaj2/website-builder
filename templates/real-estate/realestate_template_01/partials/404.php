<section class="wb-section kst-404">
  <div class="container text-center">
    <span class="kst-404-code">404</span>
    <h1 class="kst-title">This address doesn’t exist</h1>
    <p class="kst-lead">The page may have moved or the property is no longer listed.</p>
    <div class="kst-actions justify-content-center mt-4">
      <a class="btn kst-btn kst-btn-primary" href="<?= $view->e($view->listingsEnabled() ? $view->listingsUrl() : $view->url('')) ?>"><?= $view->listingsEnabled() ? 'Browse properties' : 'Back to the homepage' ?><i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</section>
