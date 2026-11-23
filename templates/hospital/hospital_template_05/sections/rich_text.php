<section <?= $view->attrs($section, 'lhc-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'lhc-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lhc-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="lhc-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
