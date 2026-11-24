<section <?= $view->attrs($section, 'erc-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'erc-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="erc-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="erc-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
