<section <?= $view->attrs($section, 'hbc-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'hbc-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hbc-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="hbc-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
