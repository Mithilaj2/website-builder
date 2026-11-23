<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'pcr-stats pcr-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="pcr-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="pcr-stat-grid" style="--pcr-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="pcr-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="pcr-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-pcr-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="pcr-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
