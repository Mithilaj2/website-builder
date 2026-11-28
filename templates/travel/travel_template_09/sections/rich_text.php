<section <?= $view->attrs($section, 'ihp-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'ihp-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ihp-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="ihp-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
