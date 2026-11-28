<section <?= $view->attrs($section, 'wwg-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'wwg-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="wwg-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="wwg-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
