<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'cse-stats cse-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cse-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="cse-stat-grid" style="--cse-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="cse-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="cse-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-cse-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="cse-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
