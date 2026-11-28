<?php
$img = $view->img($data['image'] ?? '', 'theme:images/hero.svg');
$highlights = array_values(array_filter((array) ($data['highlights'] ?? []), static fn ($h) => ($h['text'] ?? '') !== ''));
?>
<section <?= $view->attrs($section, 'hhr-hero hhr-hero-editorial') ?>>
  <div class="container">
    <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="hhr-eyebrow hhr-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
    <h1 class="hhr-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
    <div class="row g-4 g-lg-5 align-items-end hhr-hero-editorial-row">
      <div class="col-lg-5">
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="hhr-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
        <?= $view->partial('listing-search', ['sid' => (int) $section['id']]) ?>
        <?php if ($highlights): ?><ol class="hhr-hero-index"><?php foreach ($highlights as $h): ?><li><?= $view->e($h['text']) ?></li><?php endforeach; ?></ol><?php endif; ?>
      </div>
      <div class="col-lg-7">
        <div class="hhr-hero-frame"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"></div>
      </div>
    </div>
  </div>
</section>
