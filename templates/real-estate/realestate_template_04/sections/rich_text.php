<section <?= $view->attrs($section, 'mrs-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'mrs-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="mrs-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="mrs-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
