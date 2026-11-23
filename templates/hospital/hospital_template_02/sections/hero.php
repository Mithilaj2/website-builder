<?php
$bg = $view->img(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), 'theme:images/hero.svg');
$highlights = array_values(array_filter((array) ($data['highlights'] ?? []), static fn ($h) => ($h['text'] ?? '') !== ''));
?>
<section <?= $view->attrs($section, 'srh-hero srh-hero-cover') ?>>
  <div class="srh-hero-cover-bg" style="background-image:url('<?= $bg ?>')" role="img" aria-label="<?= $view->alt(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div>
  <div class="container position-relative">
    <div class="srh-hero-cover-text">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="srh-eyebrow srh-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="srh-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="srh-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'srh-btn-light']) ?>
    </div>
    <?php if ($highlights): ?>
    <ul class="srh-hero-strip" style="--srh-n:<?= min(4, count($highlights)) ?>">
      <?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2-circle') ?><span><?= $view->e($h['text']) ?></span></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>
