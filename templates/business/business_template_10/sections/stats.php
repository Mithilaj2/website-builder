<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'pls-stats pls-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="pls-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="pls-stat-grid" style="--pls-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="pls-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="pls-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-pls-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="pls-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
