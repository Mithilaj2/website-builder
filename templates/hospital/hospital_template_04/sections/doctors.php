<section <?= $view->attrs($section, 'cse-doctors cse-doctors-list') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $d): ?><div class="col-lg-6"><?= $view->partial('doctor', ['d' => $d]) ?></div><?php endforeach; ?>
    </div>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="cse-actions justify-content-center"><a class="btn cse-btn cse-btn-primary" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
  </div>
</section>
