<section <?= $view->attrs($section, 'jts-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'jts-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="jts-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="jts-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
