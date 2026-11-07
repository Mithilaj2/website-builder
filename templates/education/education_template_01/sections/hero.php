<?php
$img = $view->img($data['image'] ?? '');
$bg = $view->img($data['background_image'] ?? '');
$rating = (string) $view->site('rating');
?>
<section <?= $view->attrs($section, 'lyc-hero') ?>>
  <?php if ($bg !== ''): ?><div class="lyc-hero-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6">
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="lyc-hero-eyebrow"><i class="bi bi-stars"></i><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h1 class="lyc-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="lyc-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'lyc-btn-outline-light']) ?>
        <?php if (!empty($data['highlights'])): ?>
          <ul class="lyc-hero-highlights">
            <?php foreach ((array) $data['highlights'] as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2') ?><?= $view->e($h['text'] ?? '') ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <div class="col-lg-6">
        <div class="lyc-hero-media">
          <div class="lyc-arch"><?php if ($img !== ''): ?><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"><?php endif; ?></div>
          <span class="lyc-hero-ring" aria-hidden="true"></span>
          <?php if ($rating !== ''): ?>
            <div class="lyc-hero-badge"><span class="lyc-hero-badge-icon"><i class="bi bi-award-fill"></i></span><div><strong><?= $view->e($rating) ?></strong><?php if ($view->site('rating_label') !== ''): ?><small><?= $view->e($view->site('rating_label')) ?></small><?php endif; ?></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
