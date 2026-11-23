<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'rvp-stats rvp-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="rvp-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="rvp-stat-grid" style="--rvp-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="rvp-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="rvp-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-rvp-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="rvp-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
