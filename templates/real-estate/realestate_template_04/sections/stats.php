<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'mrs-stats mrs-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="mrs-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="mrs-stat-grid" style="--mrs-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="mrs-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="mrs-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-mrs-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="mrs-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
