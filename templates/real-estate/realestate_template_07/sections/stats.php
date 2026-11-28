<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'bwr-stats bwr-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="bwr-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="bwr-stat-grid" style="--bwr-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="bwr-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="bwr-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-bwr-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="bwr-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
