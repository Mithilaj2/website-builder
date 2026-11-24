<section <?= $view->attrs($section, 'stz-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'stz-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="stz-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="stz-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
