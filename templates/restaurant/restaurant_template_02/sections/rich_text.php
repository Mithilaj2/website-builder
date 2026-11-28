<section <?= $view->attrs($section, 'mrc-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'mrc-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="mrc-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="mrc-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
