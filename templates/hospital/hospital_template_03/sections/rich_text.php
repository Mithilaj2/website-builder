<section <?= $view->attrs($section, 'scd-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'scd-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="scd-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="scd-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
