<section <?= $view->attrs($section, 'mnw-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'mnw-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="mnw-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="mnw-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
