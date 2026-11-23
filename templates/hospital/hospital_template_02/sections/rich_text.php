<section <?= $view->attrs($section, 'srh-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'srh-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="srh-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="srh-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
