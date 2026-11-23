<?php $cols = ['2' => 'col-md-6', '3' => 'col-md-6 col-lg-4', '4' => 'col-sm-6 col-lg-3'][$data['columns'] ?? '3'] ?? 'col-md-6 col-lg-4'; ?>
<section <?= $view->attrs($section, 'lac-services lac-services-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $i => $item): $img = $view->img($item['image'] ?? ''); ?>
        <div class="<?= $cols ?>">
          <article class="lac-card lac-service<?= $img !== '' ? ' has-image' : '' ?>">
            <?php if ($img !== ''): ?><div class="lac-service-img"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div><?php endif; ?>
            <div class="lac-service-body">
              <?php if ($img === ''): ?><span class="lac-icon"><?= $view->icon(($item['icon'] ?? '') ?: 'bi-briefcase') ?></span><?php endif; ?>
              <span class="lac-service-num" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
              <h3 class="lac-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
              <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
              <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="lac-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
