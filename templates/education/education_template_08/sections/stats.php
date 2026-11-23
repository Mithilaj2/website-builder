<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'wsm-stats wsm-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="wsm-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="wsm-stat-grid" style="--wsm-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="wsm-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="wsm-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-wsm-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="wsm-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
