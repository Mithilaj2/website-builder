<?php
$img = $view->img(($data['image'] ?? '') ?: ($data['background_image'] ?? ''));
$highlights = (array) ($data['highlights'] ?? []);
?>
<section <?= $view->attrs($section, 'ldg-hero') ?>>
  <div class="container">
    <div class="row g-4 g-lg-5 align-items-center">
      <div class="col-lg-6">
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="ldg-hero-pill"><i class="bi bi-stars"></i><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h1 class="ldg-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="ldg-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
        <?php if ($highlights): ?><ul class="ldg-hero-ticks"><?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check-circle-fill') ?><span><?= $view->e($h['text'] ?? '') ?></span></li><?php endforeach; ?></ul><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'ldg-btn-outline']) ?>
      </div>
      <?php if ($img !== ''): ?>
        <div class="col-lg-6"><div class="ldg-hero-art"><img src="<?= $img ?>" alt="<?= $view->alt(($data['image'] ?? '') ?: ($data['background_image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div></div>
      <?php endif; ?>
    </div>
  </div>
</section>
