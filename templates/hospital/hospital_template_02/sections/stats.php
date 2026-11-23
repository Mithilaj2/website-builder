<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'srh-stats srh-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="srh-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="srh-stat-grid" style="--srh-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="srh-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="srh-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-srh-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="srh-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
