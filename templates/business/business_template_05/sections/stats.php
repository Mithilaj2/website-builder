<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'icw-stats icw-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="icw-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="icw-stat-grid" style="--icw-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="icw-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="icw-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-icw-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="icw-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
