<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'kkc-stats kkc-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="kkc-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="kkc-stat-grid" style="--kkc-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="kkc-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="kkc-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-kkc-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="kkc-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
