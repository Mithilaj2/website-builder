<section <?= $view->attrs($section, 'hhr-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'hhr-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hhr-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="hhr-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
