<section class="wb-section lvh-404">
  <div class="container text-center">
    <span class="lvh-404-code">404</span>
    <h1 class="lvh-title">This door leads nowhere</h1>
    <p class="lvh-lead">The page you were looking for has moved or no longer exists.</p>
    <div class="lvh-actions justify-content-center mt-4">
      <a class="btn lvh-btn lvh-btn-primary" href="<?= $view->e($view->listingsEnabled() ? $view->listingsUrl() : $view->url('')) ?>"><?= $view->listingsEnabled() ? 'See our rooms' : 'Back to the homepage' ?></a>
    </div>
  </div>
</section>
