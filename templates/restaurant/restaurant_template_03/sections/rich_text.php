<section <?= $view->attrs($section, 'atr-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'atr-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="atr-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="atr-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
