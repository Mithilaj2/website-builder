<section <?= $view->attrs($section, 'pcr-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'pcr-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="pcr-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="pcr-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
