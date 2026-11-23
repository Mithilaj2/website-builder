<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'gfh-stats gfh-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="gfh-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="gfh-stat-grid" style="--gfh-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="gfh-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="gfh-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-gfh-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="gfh-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
