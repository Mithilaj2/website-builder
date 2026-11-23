<section <?= $view->attrs($section, 'mit-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'mit-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="mit-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="mit-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
