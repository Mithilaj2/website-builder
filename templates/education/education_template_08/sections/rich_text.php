<section <?= $view->attrs($section, 'wsm-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'wsm-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="wsm-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="wsm-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
