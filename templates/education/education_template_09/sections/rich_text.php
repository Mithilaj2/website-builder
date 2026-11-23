<section <?= $view->attrs($section, 'lgb-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'lgb-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="lgb-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="lgb-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
