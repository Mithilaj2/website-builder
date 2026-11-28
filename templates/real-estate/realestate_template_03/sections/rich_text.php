<section <?= $view->attrs($section, 'nvk-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'nvk-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="nvk-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="nvk-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
