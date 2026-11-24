<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'stz-stats stz-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="stz-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="stz-stat-grid" style="--stz-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="stz-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="stz-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-stz-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="stz-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
