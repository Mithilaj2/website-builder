<section <?= $view->attrs($section, 'gft-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'gft-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="gft-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="gft-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
