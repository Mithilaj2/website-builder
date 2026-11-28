<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'wwg-stats wwg-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="wwg-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="wwg-stat-grid" style="--wwg-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="wwg-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="wwg-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-wwg-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="wwg-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
