<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'ttt-stats ttt-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ttt-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="ttt-stat-grid" style="--ttt-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="ttt-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="ttt-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-ttt-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="ttt-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
