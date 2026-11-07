<section <?= $view->attrs($section, 'lyc-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'lyc-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lyc-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="lyc-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
