<section <?= $view->attrs($section, 'bwr-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'bwr-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="bwr-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="bwr-rich"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
