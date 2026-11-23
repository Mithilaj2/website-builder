<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'psd-stats psd-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="psd-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="psd-stat-grid" style="--psd-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="psd-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="psd-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-psd-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="psd-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
