<?php
$img = $view->img($data['image'] ?? '');
$img2 = $view->img($data['image_2'] ?? '');
?>
<section <?= $view->attrs($section, 'mrs-about mrs-about-grid') ?>>
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="<?= $img !== '' ? 'col-lg-6' : 'col-lg-8 mx-auto' ?>">
        <?= $view->partial('about-body', ['data' => $data]) ?>
      </div>
      <?php if ($img !== ''): ?>
      <div class="col-lg-6">
        <div class="mrs-about-collage<?= $img2 !== '' ? ' has-second' : '' ?>">
          <img class="mrs-about-img-1" src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>" loading="lazy">
          <?php if (($data['experience_number'] ?? '') !== ''): ?><div class="mrs-about-badge"><strong><?= $view->e($data['experience_number']) ?></strong><span><?= $view->e($data['experience_label'] ?? '') ?></span></div><?php endif; ?>
          <?php if ($img2 !== ''): ?><img class="mrs-about-img-2" src="<?= $img2 ?>" alt="<?= $view->alt($data['image_2'] ?? '', '') ?>" loading="lazy"><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
