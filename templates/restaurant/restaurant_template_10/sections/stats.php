<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'pdd-stats pdd-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="pdd-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="pdd-stat-grid" style="--pdd-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="pdd-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="pdd-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-pdd-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="pdd-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
