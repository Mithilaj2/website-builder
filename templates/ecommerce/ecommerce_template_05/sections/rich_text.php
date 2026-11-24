<section <?= $view->attrs($section, 'dwp-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'dwp-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="dwp-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="dwp-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
