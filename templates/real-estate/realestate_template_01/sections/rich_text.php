<section <?= $view->attrs($section, 'kst-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'kst-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="kst-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="kst-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
