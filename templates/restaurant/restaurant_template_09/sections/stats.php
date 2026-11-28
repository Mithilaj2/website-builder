<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'sks-stats sks-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="sks-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="sks-stat-grid" style="--sks-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="sks-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="sks-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-sks-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="sks-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
