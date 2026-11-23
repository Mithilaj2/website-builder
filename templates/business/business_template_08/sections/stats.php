<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'cmc-stats cmc-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cmc-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="cmc-stat-grid" style="--cmc-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="cmc-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="cmc-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-cmc-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="cmc-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
