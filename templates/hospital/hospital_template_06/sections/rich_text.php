<section <?= $view->attrs($section, 'psd-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'psd-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="psd-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="psd-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
