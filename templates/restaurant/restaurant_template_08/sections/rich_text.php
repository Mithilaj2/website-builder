<section <?= $view->attrs($section, 'kkc-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'kkc-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="kkc-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="kkc-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
