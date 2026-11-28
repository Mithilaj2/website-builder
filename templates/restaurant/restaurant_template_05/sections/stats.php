<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'eao-stats eao-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="eao-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="eao-stat-grid" style="--eao-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="eao-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="eao-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-eao-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="eao-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
