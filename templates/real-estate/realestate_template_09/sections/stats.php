<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'hcr-stats hcr-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hcr-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="hcr-stat-grid" style="--hcr-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="hcr-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="hcr-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-hcr-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="hcr-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
