<section <?= $view->attrs($section, 'rsp-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'rsp-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="rsp-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="rsp-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
