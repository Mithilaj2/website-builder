<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'bhl-stats bhl-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="bhl-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="bhl-stat-grid" style="--bhl-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="bhl-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="bhl-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-bhl-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="bhl-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
