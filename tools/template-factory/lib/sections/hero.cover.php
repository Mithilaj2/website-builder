<?php
$bg = $view->img(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), 'theme:images/hero.svg');
$highlights = array_values(array_filter((array) ($data['highlights'] ?? []), static fn ($h) => ($h['text'] ?? '') !== ''));
?>
<section <?= $view->attrs($section, '{{p}}-hero {{p}}-hero-cover') ?>>
  <div class="{{p}}-hero-cover-bg" style="background-image:url('<?= $bg ?>')" role="img" aria-label="<?= $view->alt(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div>
  <div class="container position-relative">
    <div class="{{p}}-hero-cover-text">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="{{p}}-eyebrow {{p}}-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="{{p}}-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="{{p}}-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => '{{p}}-btn-light']) ?>
      <?= $view->partial('listing-search', ['sid' => (int) $section['id']]) ?>
    </div>
    <?php if ($highlights): ?>
    <ul class="{{p}}-hero-strip" style="--{{p}}-n:<?= min(4, count($highlights)) ?>">
      <?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2-circle') ?><span><?= $view->e($h['text']) ?></span></li><?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>
