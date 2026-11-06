<?php
$cols = ['2' => 'col-md-6', '3' => 'col-md-6 col-lg-4', '4' => 'col-md-6 col-lg-3'][$data['columns'] ?? '3'] ?? 'col-md-6 col-lg-4';
?>
<section <?= $view->attrs($section, 'mrd-services') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4">
      <?php foreach ((array) ($data['items'] ?? []) as $i => $item): $img = $view->img($item['image'] ?? ''); ?>
        <div class="<?= $cols ?>">
          <article class="mrd-card mrd-service<?= $img !== '' ? ' has-image' : '' ?>">
            <?php if ($img !== ''): ?>
              <div class="mrd-service-img"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div>
            <?php endif; ?>
            <div class="mrd-service-body">
              <?php if ($img === '' && ($item['icon'] ?? '') !== ''): ?><span class="mrd-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?>
              <span class="mrd-service-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
              <h3 class="mrd-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
              <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
              <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="mrd-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
