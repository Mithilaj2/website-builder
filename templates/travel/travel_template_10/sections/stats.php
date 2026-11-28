<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'cpt-stats cpt-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cpt-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="cpt-stat-grid" style="--cpt-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="cpt-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="cpt-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-cpt-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="cpt-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
