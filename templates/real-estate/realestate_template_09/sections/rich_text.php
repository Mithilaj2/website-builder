<section <?= $view->attrs($section, 'hcr-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'hcr-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hcr-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="hcr-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
