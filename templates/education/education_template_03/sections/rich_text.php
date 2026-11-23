<section <?= $view->attrs($section, 'apx-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'apx-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="apx-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="apx-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
