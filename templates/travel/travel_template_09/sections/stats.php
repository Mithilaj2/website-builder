<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'ihp-stats ihp-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ihp-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="ihp-stat-grid" style="--ihp-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="ihp-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="ihp-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-ihp-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="ihp-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
