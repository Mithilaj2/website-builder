<section <?= $view->attrs($section, 'ltw-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'ltw-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ltw-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="ltw-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
