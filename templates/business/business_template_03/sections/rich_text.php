<section <?= $view->attrs($section, 'vtx-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'vtx-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="vtx-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="vtx-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
