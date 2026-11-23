<section <?= $view->attrs($section, 'trm-stats') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="trm-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="row g-0 justify-content-center">
      <?php foreach ((array) ($data['items'] ?? []) as $item): ?>
        <div class="col-6 col-lg-3"><div class="trm-stat"><strong><span data-trm-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span><?= $view->e($item['label'] ?? '') ?></span></div></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
