<section <?= $view->attrs($section, 'bnb-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'bnb-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="bnb-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="bnb-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
