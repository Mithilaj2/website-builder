<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'ltw-stats ltw-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ltw-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="ltw-stat-grid" style="--ltw-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="ltw-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="ltw-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-ltw-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="ltw-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
