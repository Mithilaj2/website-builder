<section <?= $view->attrs($section, 'cfa-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'cfa-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cfa-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="cfa-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
