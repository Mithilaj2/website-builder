<section <?= $view->attrs($section, 'nbf-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'nbf-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="nbf-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="nbf-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
