<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'aur-stats aur-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="aur-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="aur-stat-grid" style="--aur-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="aur-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="aur-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-aur-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="aur-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
