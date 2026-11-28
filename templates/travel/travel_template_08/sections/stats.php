<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'hwn-stats hwn-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hwn-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="hwn-stat-grid" style="--hwn-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="hwn-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="hwn-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-hwn-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="hwn-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
