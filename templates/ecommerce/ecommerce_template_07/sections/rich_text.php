<section <?= $view->attrs($section, 'ttt-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'ttt-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ttt-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="ttt-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
