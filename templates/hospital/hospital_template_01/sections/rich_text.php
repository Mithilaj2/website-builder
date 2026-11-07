<section <?= $view->attrs($section, 'crl-richtext') ?>>
  <div class="container">
    <div class="<?= ($data['width'] ?? 'narrow') === 'wide' ? '' : 'crl-narrow' ?>">
      <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="crl-title mb-4"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
      <div class="crl-prose"><?= $view->rich($data['content'] ?? '') ?></div>
    </div>
  </div>
</section>
