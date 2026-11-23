<section <?= $view->attrs($section, 'cmc-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'cmc-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cmc-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="cmc-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
