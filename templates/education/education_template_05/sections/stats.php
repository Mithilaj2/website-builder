<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'cis-stats cis-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cis-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="cis-stat-grid" style="--cis-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="cis-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="cis-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-cis-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="cis-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
