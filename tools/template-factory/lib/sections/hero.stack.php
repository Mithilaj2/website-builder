<?php
$img = $view->img($data['image'] ?? '', 'theme:images/hero.svg');
$highlights = array_values(array_filter((array) ($data['highlights'] ?? []), static fn ($h) => ($h['text'] ?? '') !== ''));
?>
<section <?= $view->attrs($section, '{{p}}-hero {{p}}-hero-stack') ?>>
  <div class="{{p}}-hero-glow" aria-hidden="true"></div>
  <div class="container position-relative">
    <div class="{{p}}-hero-stack-text">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="{{p}}-eyebrow {{p}}-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="{{p}}-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="{{p}}-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'extra' => 'justify-content-center']) ?>
    </div>
    <div class="{{p}}-hero-stage">
      <div class="{{p}}-hero-frame"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"></div>
      <?php if ($highlights): ?>
      <ul class="{{p}}-hero-chips">
        <?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2-circle') ?><?= $view->e($h['text']) ?></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>
  </div>
</section>
