<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'cvl-stats cvl-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cvl-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="cvl-stat-grid" style="--cvl-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="cvl-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="cvl-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-cvl-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="cvl-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
