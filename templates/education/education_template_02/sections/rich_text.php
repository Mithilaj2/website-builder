<section <?= $view->attrs($section, 'lac-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'lac-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lac-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="lac-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
