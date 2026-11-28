<section <?= $view->attrs($section, 'pdd-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'pdd-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="pdd-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="pdd-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
