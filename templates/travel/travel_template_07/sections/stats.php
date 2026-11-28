<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'rrj-stats rrj-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="rrj-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="rrj-stat-grid" style="--rrj-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="rrj-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="rrj-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-rrj-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="rrj-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
