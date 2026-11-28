<?php
$img = $view->img($data['image'] ?? '', 'theme:images/hero.svg');
$highlights = array_values(array_filter((array) ($data['highlights'] ?? []), static fn ($h) => ($h['text'] ?? '') !== ''));
?>
<section <?= $view->attrs($section, 'ltw-hero ltw-hero-stack') ?>>
  <div class="ltw-hero-glow" aria-hidden="true"></div>
  <div class="container position-relative">
    <div class="ltw-hero-stack-text">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="ltw-eyebrow ltw-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="ltw-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="ltw-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'extra' => 'justify-content-center']) ?>
      <?= $view->partial('listing-search', ['sid' => (int) $section['id']]) ?>
    </div>
    <div class="ltw-hero-stage">
      <div class="ltw-hero-frame"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"></div>
      <?php if ($highlights): ?>
      <ul class="ltw-hero-chips">
        <?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2-circle') ?><?= $view->e($h['text']) ?></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
