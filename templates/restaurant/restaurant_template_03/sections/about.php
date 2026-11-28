<?php $img = $view->img($data['image'] ?? ''); ?>
<section <?= $view->attrs($section, 'atr-about atr-about-single') ?>>
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="<?= $img !== '' ? 'col-lg-6 order-lg-2' : 'col-lg-8 mx-auto' ?>">
        <?= $view->partial('about-body', ['data' => $data]) ?>
      </div>
      <?php if ($img !== ''): ?>
      <div class="col-lg-6 order-lg-1">
        <div class="atr-about-media">
          <img class="atr-about-img-1" src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>" loading="lazy">
          <?php if (($data['experience_number'] ?? '') !== ''): ?><div class="atr-about-badge"><strong><?= $view->e($data['experience_number']) ?></strong><span><?= $view->e($data['experience_label'] ?? '') ?></span></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
