<section <?= $view->attrs($section, '{{p}}-doctors {{p}}-doctors-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $d): ?><div class="col-sm-6 col-lg-3"><?= $view->partial('doctor', ['d' => $d]) ?></div><?php endforeach; ?>
    </div>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="{{p}}-actions justify-content-center"><a class="btn {{p}}-btn {{p}}-btn-primary" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a></div><?php endif; ?>
  </div>
</section>
