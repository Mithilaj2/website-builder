<section <?= $view->attrs($section, 'bhl-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'bhl-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="bhl-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="bhl-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
