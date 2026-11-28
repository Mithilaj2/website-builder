<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'atr-stats atr-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="atr-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="atr-stat-grid" style="--atr-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="atr-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="atr-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-atr-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="atr-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
