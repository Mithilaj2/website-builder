<section <?= $view->attrs($section, 'cvl-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'cvl-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cvl-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="cvl-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
