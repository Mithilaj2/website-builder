<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'lhc-stats lhc-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lhc-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="lhc-stat-grid" style="--lhc-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="lhc-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="lhc-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-lhc-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="lhc-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
