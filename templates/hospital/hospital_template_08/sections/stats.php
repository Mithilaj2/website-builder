<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'vga-stats vga-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="vga-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="vga-stat-grid" style="--vga-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="vga-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="vga-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-vga-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="vga-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
