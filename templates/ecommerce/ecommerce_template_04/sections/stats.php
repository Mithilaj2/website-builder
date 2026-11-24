<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'gbo-stats gbo-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="gbo-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="gbo-stat-grid" style="--gbo-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="gbo-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="gbo-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-gbo-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="gbo-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
