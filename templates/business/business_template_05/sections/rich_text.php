<section <?= $view->attrs($section, 'icw-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'icw-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="icw-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="icw-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
