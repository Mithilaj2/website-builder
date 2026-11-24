<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'dwp-stats dwp-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="dwp-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="dwp-stat-grid" style="--dwp-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="dwp-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="dwp-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-dwp-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="dwp-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
