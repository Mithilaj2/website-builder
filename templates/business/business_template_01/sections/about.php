<?php
$img1 = $view->img($data['image'] ?? '', 'theme:images/about-1.svg');
$img2 = $view->img($data['image_2'] ?? '', 'theme:images/about-2.svg');
$points = array_filter((array) ($data['points'] ?? []), static fn ($p) => ($p['text'] ?? '') !== '');
?>
<section <?= $view->attrs($section, 'mrd-about') ?>>
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6">
        <div class="mrd-about-media">
          <img class="mrd-about-img-1" src="<?= $img1 ?>" alt="<?= $view->alt($data['image'] ?? '', 'About us') ?>">
          <img class="mrd-about-img-2" src="<?= $img2 ?>" alt="<?= $view->alt($data['image_2'] ?? '', '') ?>">
          <?php if (($data['experience_number'] ?? '') !== ''): ?>
            <div class="mrd-about-badge"><strong><?= $view->e($data['experience_number']) ?></strong><span><?= $view->e($data['experience_label'] ?? '') ?></span></div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-lg-6">
        <?= $view->partial('section-head', ['data' => ['eyebrow' => $data['eyebrow'] ?? '', 'heading' => $data['heading'] ?? ''], 'align' => 'start']) ?>
        <div class="mrd-rich"><?= $view->rich($data['description'] ?? '') ?></div>
        <?php if ($points): ?>
          <ul class="mrd-checks">
            <?php foreach ($points as $p): ?><li><i class="bi bi-check-lg"></i><?= $view->e($p['text']) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['button'] ?? []]) ?>
      </div>
    </div>
  </div>
</section>
