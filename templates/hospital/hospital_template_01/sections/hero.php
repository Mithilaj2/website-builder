<?php
$img = $view->img($data['image'] ?? '');
$bg = $view->img($data['background_image'] ?? '');
$rating = (string) $view->site('rating');
$emergency = (string) $view->site('emergency_phone');
$highlights = (array) ($data['highlights'] ?? []);
?>
<section <?= $view->attrs($section, 'crl-hero' . ($highlights ? ' has-quick' : '')) ?>>
  <?php if ($bg !== ''): ?><div class="crl-hero-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <svg class="crl-hero-pulse" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true"><path d="M0 70h520l26-46 30 92 28-74 18 28h818" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <div class="container position-relative">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6">
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="crl-hero-eyebrow"><i class="bi bi-shield-plus"></i><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h1 class="crl-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="crl-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'crl-btn-outline']) ?>
      </div>
      <div class="col-lg-6">
        <div class="crl-hero-media">
          <div class="crl-frame crl-frame-hero"><?php if ($img !== ''): ?><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"><?php endif; ?></div>
          <?php if ($emergency !== ''): ?>
            <a class="crl-float crl-float-emergency" href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', $emergency)) ?>"><span class="crl-float-icon"><i class="bi bi-telephone-inbound-fill"></i></span><span><small>24×7 emergency</small><strong><?= $view->e($emergency) ?></strong></span></a>
          <?php endif; ?>
          <?php if ($rating !== ''): ?>
            <div class="crl-float crl-float-badge"><span class="crl-float-icon is-teal"><i class="bi bi-patch-check-fill"></i></span><span><strong><?= $view->e($rating) ?></strong><?php if ($view->site('rating_label') !== ''): ?><small><?= $view->e($view->site('rating_label')) ?></small><?php endif; ?></span></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php if ($highlights): ?>
    <div class="container position-relative">
      <ul class="crl-quick" style="--crl-quick:<?= max(1, min(4, count($highlights))) ?>">
        <?php foreach ($highlights as $h): ?><li><span class="crl-icon"><?= $view->icon(($h['icon'] ?? '') ?: 'bi-plus-lg') ?></span><span><?= $view->e($h['text'] ?? '') ?></span></li><?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</section>
