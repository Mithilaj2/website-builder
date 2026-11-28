<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'bnb-stats bnb-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="bnb-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="bnb-stat-grid" style="--bnb-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="bnb-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="bnb-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-bnb-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="bnb-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
