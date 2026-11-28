<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'fnl-stats fnl-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="fnl-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="fnl-stat-grid" style="--fnl-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="fnl-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="fnl-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-fnl-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="fnl-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
