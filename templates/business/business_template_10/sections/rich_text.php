<section <?= $view->attrs($section, 'pls-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'pls-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="pls-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="pls-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
