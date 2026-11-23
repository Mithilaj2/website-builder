<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'apx-stats apx-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="apx-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="apx-stat-grid" style="--apx-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="apx-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="apx-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-apx-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="apx-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
