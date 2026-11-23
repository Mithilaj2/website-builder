<section <?= $view->attrs($section, 'cis-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'cis-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cis-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="cis-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
