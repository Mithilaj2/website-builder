<section <?= $view->attrs($section, 'bpt-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'bpt-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="bpt-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="bpt-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
