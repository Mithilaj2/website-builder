<section <?= $view->attrs($section, 'trm-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'trm-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="trm-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="trm-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
