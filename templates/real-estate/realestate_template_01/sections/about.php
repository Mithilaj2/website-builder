<?php
$img = $view->img($data['image'] ?? '');
$img2 = $view->img($data['image_2'] ?? '');
?>
<section <?= $view->attrs($section, 'kst-about') ?>>
  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">
      <?php if ($img !== ''): ?>
      <div class="col-lg-6">
        <div class="kst-about-media<?= $img2 !== '' ? ' has-second' : '' ?>">
          <div class="kst-frame"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>" loading="lazy"></div>
          <?php if ($img2 !== ''): ?><div class="kst-pill kst-about-small"><img src="<?= $img2 ?>" alt="<?= $view->alt($data['image_2'] ?? '', '') ?>" loading="lazy"></div><?php endif; ?>
          <?php if (($data['experience_number'] ?? '') !== ''): ?><div class="kst-about-badge"><strong><?= $view->e($data['experience_number']) ?></strong><span><?= $view->e($data['experience_label'] ?? '') ?></span></div><?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="<?= $img !== '' ? 'col-lg-6' : 'col-lg-8 mx-auto' ?>">
        <?= $view->partial('section-head', ['data' => ['eyebrow' => $data['eyebrow'] ?? '', 'heading' => $data['heading'] ?? ''], 'align' => 'start']) ?>
        <div class="kst-prose"><?= $view->rich($data['description'] ?? '') ?></div>
        <?php if (!empty($data['points'])): ?><ul class="kst-ticks"><?php foreach ((array) $data['points'] as $pt): ?><li><i class="bi bi-check2"></i><?= $view->e($pt['text'] ?? '') ?></li><?php endforeach; ?></ul><?php endif; ?>
        <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="btn kst-btn kst-btn-outline mt-2" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
      </div>
    </div>
  </div>
</section>
