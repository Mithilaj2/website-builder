<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'vtx-stats vtx-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="vtx-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="vtx-stat-grid" style="--vtx-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="vtx-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="vtx-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-vtx-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="vtx-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
