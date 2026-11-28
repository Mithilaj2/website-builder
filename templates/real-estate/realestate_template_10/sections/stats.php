<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'hhr-stats hhr-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hhr-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="hhr-stat-grid" style="--hhr-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="hhr-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="hhr-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-hhr-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="hhr-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
