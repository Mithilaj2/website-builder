<?php
$bg = $view->img(($data['background_image'] ?? '') ?: ($data['image'] ?? ''));
$highlights = (array) ($data['highlights'] ?? []);
?>
<section <?= $view->attrs($section, 'sfr-hero') ?>>
  <?php if ($bg !== ''): ?><div class="sfr-hero-bg" style="background-image:url('<?= $bg ?>')" role="img" aria-label="<?= $view->alt(($data['background_image'] ?? '') ?: ($data['image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div><?php endif; ?>
  <div class="container position-relative">
    <div class="sfr-hero-copy">
      <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="sfr-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
      <h1 class="sfr-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="sfr-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'sfr-btn-outline']) ?>
    </div>
    <?php if ($highlights): ?>
      <ul class="sfr-hero-points">
        <?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-dot') ?><span><?= $view->e($h['text'] ?? '') ?></span></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
