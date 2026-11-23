<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'cfa-stats cfa-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cfa-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="cfa-stat-grid" style="--cfa-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="cfa-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="cfa-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-cfa-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="cfa-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
