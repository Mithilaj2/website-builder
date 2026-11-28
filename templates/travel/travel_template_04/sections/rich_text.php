<section <?= $view->attrs($section, 'yst-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'yst-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="yst-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="yst-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
