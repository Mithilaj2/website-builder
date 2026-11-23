<section <?= $view->attrs($section, 'chi-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'chi-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="chi-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="chi-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
