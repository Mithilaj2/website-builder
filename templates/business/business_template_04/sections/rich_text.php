<section <?= $view->attrs($section, 'gfh-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'gfh-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="gfh-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="gfh-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
