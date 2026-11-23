<section <?= $view->attrs($section, 'sfr-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'sfr-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="sfr-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="sfr-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
