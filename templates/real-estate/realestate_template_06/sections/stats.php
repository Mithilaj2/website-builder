<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'hhv-stats hhv-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hhv-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="hhv-stat-grid" style="--hhv-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="hhv-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="hhv-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-hhv-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="hhv-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
