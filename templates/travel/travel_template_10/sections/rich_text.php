<section <?= $view->attrs($section, 'cpt-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'cpt-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cpt-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="cpt-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
