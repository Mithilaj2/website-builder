<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'jts-stats jts-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="jts-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="jts-stat-grid" style="--jts-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="jts-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="jts-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-jts-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="jts-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
