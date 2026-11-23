<section <?= $view->attrs($section, 'ldg-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'ldg-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ldg-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="ldg-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
