<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'rsp-stats rsp-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="rsp-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="rsp-stat-grid" style="--rsp-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="rsp-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="rsp-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-rsp-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="rsp-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
