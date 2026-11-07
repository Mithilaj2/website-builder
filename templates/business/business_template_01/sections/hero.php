<?php
$img = $view->img($data['image'] ?? '', 'theme:images/hero.svg');
$bg = $view->img($data['background_image'] ?? '');
$stats = (array) ($data['highlights'] ?? []);
?>
<section <?= $view->attrs($section, 'mrd-hero') ?>>
  <?php if ($bg !== ''): ?><div class="mrd-hero-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="mrd-hero-shape" aria-hidden="true"></div>
  <div class="container position-relative">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="mrd-eyebrow mrd-eyebrow-pill"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h1 class="mrd-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="mrd-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
        <?php if ($stats): ?>
          <ul class="mrd-hero-highlights">
            <?php foreach ($stats as $h): if (($h['text'] ?? '') === '') { continue; } ?>
              <li><?= $view->icon($h['icon'] ?? 'bi-check2-circle') ?><?= $view->e($h['text']) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <div class="col-lg-6">
        <div class="mrd-hero-visual">
          <div class="mrd-hero-frame"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"></div>
          <?php if ($view->site('rating') !== ''): ?>
          <div class="mrd-hero-badge" aria-hidden="true">
            <span class="mrd-hero-badge-icon"><i class="bi bi-patch-check-fill"></i></span>
            <span><strong><?= $view->e($view->site('rating', 'Trusted')) ?></strong><small><?= $view->e($view->site('rating_label', 'by clients across India')) ?></small></span>
          </div>
          <?php endif; ?>
          <span class="mrd-hero-dots" aria-hidden="true"></span>
        </div>
      </div>
    </div>
  </div>
</section>
