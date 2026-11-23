<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'lac-stats lac-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lac-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="lac-stat-grid" style="--lac-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="lac-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="lac-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-lac-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="lac-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
