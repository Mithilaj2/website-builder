<?php
$bg = $view->img(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), 'theme:images/hero.svg');
$highlights = array_values(array_filter((array) ($data['highlights'] ?? []), static fn ($h) => ($h['text'] ?? '') !== ''));
?>
<section <?= $view->attrs($section, 'rvp-hero rvp-hero-cover') ?>>
  <div class="rvp-hero-cover-bg" style="background-image:url('<?= $bg ?>')" role="img" aria-label="<?= $view->alt(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div>
  <div class="container position-relative">
    <div class="rvp-hero-cover-text">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="rvp-eyebrow rvp-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="rvp-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="rvp-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'rvp-btn-light']) ?>
    </div>
    <?php if ($highlights): ?>
    <ul class="rvp-hero-strip" style="--rvp-n:<?= min(4, count($highlights)) ?>">
      <?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2-circle') ?><span><?= $view->e($h['text']) ?></span></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>
