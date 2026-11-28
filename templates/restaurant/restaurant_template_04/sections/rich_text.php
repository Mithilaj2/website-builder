<section <?= $view->attrs($section, 'fnl-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'fnl-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="fnl-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="fnl-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
