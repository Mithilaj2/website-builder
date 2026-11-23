<section <?= $view->attrs($section, 'cse-testimonials cse-testimonials-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $t): ?><div class="col-md-6 col-lg-4"><?= $view->partial('quote', ['t' => $t]) ?></div><?php endforeach; ?>
    </div>
  </div>
</section>
