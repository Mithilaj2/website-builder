<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'chi-stats chi-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="chi-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="chi-stat-grid" style="--chi-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="chi-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="chi-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-chi-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="chi-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
