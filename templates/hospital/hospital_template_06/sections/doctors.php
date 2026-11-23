<section <?= $view->attrs($section, 'psd-doctors psd-doctors-list') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $d): ?><div class="col-lg-6"><?= $view->partial('doctor', ['d' => $d]) ?></div><?php endforeach; ?>
    </div>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="psd-actions justify-content-center"><a class="btn psd-btn psd-btn-primary" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
  </div>
</section>
