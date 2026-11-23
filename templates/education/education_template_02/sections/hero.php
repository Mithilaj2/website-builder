<?php
$img = $view->img($data['image'] ?? '', 'theme:images/hero.svg');
$bg = $view->img($data['background_image'] ?? '');
$highlights = (array) ($data['highlights'] ?? []);
?>
<section <?= $view->attrs($section, 'lac-hero lac-hero-split') ?>>
  <?php if ($bg !== ''): ?><div class="lac-hero-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6">
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="lac-eyebrow lac-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h1 class="lac-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="lac-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
        <?php if ($highlights): ?><ul class="lac-highlights"><?php foreach ($highlights as $h): if (($h['text'] ?? '') === '') { continue; } ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2-circle') ?><?= $view->e($h['text']) ?></li><?php endforeach; ?></ul><?php endif; ?>
      </div>
      <div class="col-lg-6">
        <div class="lac-hero-visual">
          <div class="lac-hero-frame"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"></div>
          <?php if ($view->site('rating') !== ''): ?>
          <div class="lac-hero-badge">
            <span class="lac-hero-badge-icon"><i class="bi bi-patch-check-fill"></i></span>
            <span><strong><?= $view->e($view->site('rating')) ?></strong><small><?= $view->e($view->site('rating_label', '')) ?></small></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
