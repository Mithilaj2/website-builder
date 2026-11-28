<section <?= $view->attrs($section, 'hhv-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'hhv-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hhv-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="hhv-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
