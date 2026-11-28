<section <?= $view->attrs($section, 'smt-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'smt-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="smt-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="smt-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
