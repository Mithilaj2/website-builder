<section <?= $view->attrs($section, 'vlt-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'vlt-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="vlt-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="vlt-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
