<section <?= $view->attrs($section, 'aur-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'aur-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="aur-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="aur-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
