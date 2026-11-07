<section class="wb-section trm-404">
  <div class="container text-center">
    <span class="trm-404-code">404</span>
    <h1 class="trm-title">This page has wandered off</h1>
    <p class="trm-lead">The page may have moved. Our products are still right where you left them.</p>
    <div class="trm-actions justify-content-center">
      <a class="btn trm-btn trm-btn-primary" href="<?= $view->e($view->shopEnabled() ? $view->shopUrl() : $view->url('')) ?>"><?= $view->shopEnabled() ? 'Browse the shop' : 'Back to the homepage' ?><i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</section>
