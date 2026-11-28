<section <?= $view->attrs($section, 'ccm-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'ccm-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ccm-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="ccm-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
