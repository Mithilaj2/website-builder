<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'kth-stats kth-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="kth-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="kth-stat-grid" style="--kth-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="kth-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="kth-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-kth-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="kth-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
