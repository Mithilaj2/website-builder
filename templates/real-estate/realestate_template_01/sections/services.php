<?php $cols = ['2' => 'col-md-6', '3' => 'col-md-6 col-lg-4', '4' => 'col-sm-6 col-lg-3'][$data['columns'] ?? '4'] ?? 'col-sm-6 col-lg-3'; ?>
<section <?= $view->attrs($section, 'kst-services') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4">
      <?php foreach ((array) ($data['items'] ?? []) as $item): $img = $view->img($item['image'] ?? ''); ?>
        <div class="<?= $cols ?>">
          <article class="kst-tile<?= $img !== '' ? ' has-image' : '' ?>">
            <?php if ($img !== ''): ?><div class="kst-tile-img"><img src="<?= $img ?>" alt="<?= $view->alt($item['image'] ?? '', (string) ($item['title'] ?? '')) ?>" loading="lazy"></div>
            <?php else: ?><span class="kst-icon"><?= $view->icon(($item['icon'] ?? '') ?: 'bi-book') ?></span><?php endif; ?>
            <h3 class="kst-card-title"><?= $view->e($item['title'] ?? '') ?></h3>
            <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
            <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="kst-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
