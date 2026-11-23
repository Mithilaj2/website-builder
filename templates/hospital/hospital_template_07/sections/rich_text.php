<section <?= $view->attrs($section, 'rvp-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'rvp-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="rvp-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="rvp-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
