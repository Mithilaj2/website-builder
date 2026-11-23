<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'bpt-stats bpt-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="bpt-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="bpt-stat-grid" style="--bpt-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="bpt-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="bpt-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-bpt-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="bpt-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
