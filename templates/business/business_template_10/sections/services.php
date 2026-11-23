<?php $cols = ['2' => 'col-md-6', '3' => 'col-md-6 col-lg-4', '4' => 'col-sm-6 col-lg-3'][$data['columns'] ?? '3'] ?? 'col-md-6 col-lg-4'; ?>
<section <?= $view->attrs($section, 'pls-services pls-services-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $i => $item): $img = $view->img($item['image'] ?? ''); ?>
        <div class="<?= $cols ?>">
          <article class="pls-card pls-service<?= $img !== '' ? ' has-image' : '' ?>">
            <?php if ($img !== ''): ?><div class="pls-service-img"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div><?php endif; ?>
            <div class="pls-service-body">
              <?php if ($img === ''): ?><span class="pls-icon"><?= $view->icon(($item['icon'] ?? '') ?: 'bi-briefcase') ?></span><?php endif; ?>
              <span class="pls-service-num" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
              <h3 class="pls-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
              <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
              <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="pls-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
