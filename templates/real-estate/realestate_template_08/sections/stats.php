<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'gft-stats gft-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="gft-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="gft-stat-grid" style="--gft-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="gft-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="gft-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-gft-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="gft-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
