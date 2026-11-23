<section <?= $view->attrs($section, 'scr-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'scr-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="scr-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="scr-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
