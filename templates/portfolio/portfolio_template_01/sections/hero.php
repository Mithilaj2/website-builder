<?php
$highlights = (array) ($data['highlights'] ?? []);
$img = $view->img(($data['image'] ?? '') ?: ($data['background_image'] ?? ''));
?>
<section <?= $view->attrs($section, 'skl-hero') ?>>
  <div class="container">
    <?php if (($data['eyebrow'] ?? '') !== ''): ?><p class="skl-hero-status"><span class="skl-live" aria-hidden="true"></span><?= $view->e($data['eyebrow']) ?></p><?php endif; ?>
    <h1 class="skl-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
    <div class="skl-hero-row">
      <?php if (($data['subheading'] ?? '') !== ''): ?><p class="skl-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'skl-btn-line']) ?>
    </div>
    <?php if ($img !== ''): ?><div class="skl-hero-media"><img src="<?= $img ?>" alt="<?= $view->alt(($data['image'] ?? '') ?: ($data['background_image'] ?? ''), (string) ($data['heading'] ?? '')) ?>"></div><?php endif; ?>
  </div>
  <?php if ($highlights): ?>
    <div class="skl-ticker" aria-label="What we do">
      <ul class="skl-ticker-track">
        <?php for ($k = 0; $k < 2; $k++): foreach ($highlights as $h): ?><li<?= $k ? ' aria-hidden="true"' : '' ?>><?= $view->icon(($h['icon'] ?? '') ?: 'bi-asterisk') ?><?= $view->e($h['text'] ?? '') ?></li><?php endforeach; endfor; ?>
      </ul>
    </div>
  <?php endif; ?>
</section>
