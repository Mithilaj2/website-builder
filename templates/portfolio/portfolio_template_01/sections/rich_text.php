<section <?= $view->attrs($section, 'skl-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'skl-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="skl-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="skl-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
