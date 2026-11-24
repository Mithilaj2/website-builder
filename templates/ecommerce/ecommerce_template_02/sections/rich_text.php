<section <?= $view->attrs($section, 'kth-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'kth-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="kth-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="kth-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
