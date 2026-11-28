<section <?= $view->attrs($section, 'sks-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'sks-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="sks-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="sks-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
