<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'nvk-stats nvk-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="nvk-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="nvk-stat-grid" style="--nvk-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="nvk-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="nvk-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-nvk-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="nvk-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
