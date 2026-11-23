<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'mnw-stats mnw-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="mnw-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="mnw-stat-grid" style="--mnw-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="mnw-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="mnw-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-mnw-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="mnw-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
