<section <?= $view->attrs($section, 'gbo-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'gbo-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="gbo-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="gbo-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
