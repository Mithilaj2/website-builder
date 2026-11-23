<section <?= $view->attrs($section, 'vga-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'vga-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="vga-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="vga-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
