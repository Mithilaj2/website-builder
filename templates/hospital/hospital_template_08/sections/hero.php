<?php
$img = $view->img($data['image'] ?? '', 'theme:images/hero.svg');
$highlights = array_values(array_filter((array) ($data['highlights'] ?? []), static fn ($h) => ($h['text'] ?? '') !== ''));
?>
<section <?= $view->attrs($section, 'vga-hero vga-hero-panel') ?>>
  <div class="vga-hero-panel-media"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"></div>
  <div class="container">
    <div class="row">
      <div class="col-lg-6">
        <div class="vga-hero-panel-text">
          <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="vga-eyebrow vga-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
          <h1 class="vga-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
          <?php if (($data['subheading'] ?? '') !== ''): ?><p class="vga-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
          <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'vga-btn-light']) ?>
          <?php if ($highlights): ?><ul class="vga-highlights"><?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2-circle') ?><?= $view->e($h['text']) ?></li><?php endforeach; ?></ul><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
