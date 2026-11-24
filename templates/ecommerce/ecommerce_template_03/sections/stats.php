<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'vlt-stats vlt-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="vlt-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="vlt-stat-grid" style="--vlt-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="vlt-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="vlt-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-vlt-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="vlt-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
