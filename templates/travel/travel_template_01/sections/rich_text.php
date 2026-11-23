<section <?= $view->attrs($section, 'hzt-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'hzt-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hzt-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="hzt-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
