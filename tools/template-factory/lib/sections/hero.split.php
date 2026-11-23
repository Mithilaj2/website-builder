<?php
$img = $view->img($data['image'] ?? '', 'theme:images/hero.svg');
$bg = $view->img($data['background_image'] ?? '');
$highlights = (array) ($data['highlights'] ?? []);
?>
<section <?= $view->attrs($section, '{{p}}-hero {{p}}-hero-split') ?>>
  <?php if ($bg !== ''): ?><div class="{{p}}-hero-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative">
    <div class="row align-items-center g-4 g-lg-5">
      <div class="col-lg-6">
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="{{p}}-eyebrow {{p}}-eyebrow-hero"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h1 class="{{p}}-hero-title"><?= $view->e($data['heading'] ?? '') ?></h1>
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="{{p}}-hero-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['primary_button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
        <?php if ($highlights): ?><ul class="{{p}}-highlights"><?php foreach ($highlights as $h): if (($h['text'] ?? '') === '') { continue; } ?><li><?= $view->icon(($h['icon'] ?? '') ?: 'bi-check2-circle') ?><?= $view->e($h['text']) ?></li><?php endforeach; ?></ul><?php endif; ?>
      </div>
      <div class="col-lg-6">
        <div class="{{p}}-hero-visual">
          <div class="{{p}}-hero-frame"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>"></div>
          <?php if ($view->site('rating') !== ''): ?>
          <div class="{{p}}-hero-badge">
            <span class="{{p}}-hero-badge-icon"><i class="bi bi-patch-check-fill"></i></span>
            <span><strong><?= $view->e($view->site('rating')) ?></strong><small><?= $view->e($view->site('rating_label', '')) ?></small></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
