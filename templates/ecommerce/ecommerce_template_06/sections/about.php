<?php
$img = $view->img($data['image'] ?? '');
$img2 = $view->img($data['image_2'] ?? '');
?>
<section <?= $view->attrs($section, 'aur-about aur-about-layered') ?>>
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <?php if ($img !== ''): ?>
      <div class="col-lg-6">
        <div class="aur-about-media<?= $img2 !== '' ? ' has-second' : '' ?>">
          <img class="aur-about-img-1" src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>" loading="lazy">
          <?php if ($img2 !== ''): ?><img class="aur-about-img-2" src="<?= $img2 ?>" alt="<?= $view->alt($data['image_2'] ?? '', '') ?>" loading="lazy"><?php endif; ?>
          <?php if (($data['experience_number'] ?? '') !== ''): ?><div class="aur-about-badge"><strong><?= $view->e($data['experience_number']) ?></strong><span><?= $view->e($data['experience_label'] ?? '') ?></span></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="<?= $img !== '' ? 'col-lg-6' : 'col-lg-8 mx-auto' ?>">
        <?= $view->partial('about-body', ['data' => $data]) ?>
      </div>
    </div>
  </div>
</section>
