<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'smt-stats smt-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="smt-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="smt-stat-grid" style="--smt-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="smt-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="smt-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-smt-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="smt-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
