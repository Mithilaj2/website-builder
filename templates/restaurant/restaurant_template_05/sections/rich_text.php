<section <?= $view->attrs($section, 'eao-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'eao-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="eao-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="eao-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
