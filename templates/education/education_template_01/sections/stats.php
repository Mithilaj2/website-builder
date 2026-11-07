<section <?= $view->attrs($section, 'lyc-stats') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lyc-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="row g-0 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $item): ?>
        <div class="col-6 col-lg-3">
          <div class="lyc-stat">
            <?php if (($item['icon'] ?? '') !== ''): ?><span class="lyc-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?>
            <strong><span data-lyc-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong>
            <span><?= $view->e($item['label'] ?? '') ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
