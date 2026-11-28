<section <?= $view->attrs($section, 'rrj-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'rrj-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="rrj-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="rrj-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
