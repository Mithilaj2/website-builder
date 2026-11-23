<?php
$img = $view->img($data['image'] ?? '');
$bg = $view->img($data['background_image'] ?? '');
$highlights = (array) ($data['highlights'] ?? []);
$feat = $view->products(['featured' => true, 'limit' => 1]);
$pick = $feat[0] ?? null;
?>
<section <?= $view->attrs($section, 'trm-hero') ?>>
  <?php if ($bg !== ''): ?><div class="trm-hero-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6 order-2 order-lg-1">
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="trm-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h1 class="trm-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="trm-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
        <?php if ($highlights): ?>
          <ul class="trm-hero-points"><?php foreach ($highlights as $h): ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2') ?><?= $view->e($h['text'] ?? '') ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
      </div>
      <div class="col-lg-6 order-1 order-lg-2">
        <div class="trm-hero-media">
          <div class="trm-pill trm-hero-main"><?php if ($img !== ''): ?><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"><?php endif; ?></div>
          <?php if ($pick): $pimg = $view->img($pick['image'] ?? ''); ?>
            <a class="trm-hero-pick" href="<?= $view->e($view->productUrl($pick)) ?>">
              <?php if ($pimg !== ''): ?><img src="<?= $pimg ?>" alt=""><?php endif; ?>
              <span><small>Bestseller</small><strong><?= $view->e($pick['name']) ?></strong><em><?= $view->e($view->money($pick['display_price'])) ?></em></span>
            </a>
          <?php endif; ?>
          <span class="trm-hero-stamp" aria-hidden="true"><svg viewBox="0 0 120 120"><defs><path id="trmc" d="M60 60m-44 0a44 44 0 1 1 88 0a44 44 0 1 1-88 0"/></defs><text><textPath href="#trmc">HANDMADE · SMALL BATCH · INDIA · </textPath></text></svg><i class="bi bi-flower1"></i></span>
        </div>
      </div>
    </div>
  </div>
</section>
