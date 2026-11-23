<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'scd-stats scd-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="scd-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="scd-stat-grid" style="--scd-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="scd-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="scd-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-scd-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="scd-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
