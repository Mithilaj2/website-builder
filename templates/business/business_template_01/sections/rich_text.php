<section <?= $view->attrs($section, 'mrd-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'mrd-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="mrd-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="mrd-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
