<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'lgb-stats lgb-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lgb-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="lgb-stat-grid" style="--lgb-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="lgb-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="lgb-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-lgb-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="lgb-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
