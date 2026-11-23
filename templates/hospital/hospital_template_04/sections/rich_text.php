<section <?= $view->attrs($section, 'cse-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'cse-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="cse-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="cse-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
