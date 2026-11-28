<section <?= $view->attrs($section, 'hwn-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'hwn-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="hwn-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="hwn-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
